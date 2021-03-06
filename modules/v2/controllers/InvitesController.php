<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\InputNotification;
use app\modules\v2\components\Pricing;
use app\modules\v2\components\{Utility, SharedConstant};
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\SchoolRole;
use Yii;
use yii\filters\{AccessControl, VerbFilter, ContentNegotiator};
use yii\filters\auth\CompositeAuth;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;
use app\modules\v2\models\{Schools, StudentSchool, User, InviteLog, SchoolTeachers, SchoolAdmin, Parents, TeacherClass};
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

/**
 * Invite controller
 */
class InvitesController extends ActiveController
{
    public $modelClass = 'api\v2\models\User';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];
        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'except' => ['validate-invite-token', 'verify'],
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
    }

    public function actionSchoolAdmin()
    {
        $form = new InviteLog(['scenario' => InviteLog::SCENARIO_SCHOOL_INVITE_ADMIN]);
        $form->attributes = Yii::$app->request->post();

        if (Yii::$app->user->identity->type != 'school' || !SchoolRole::find()->where(['slug' => $form->role])->exists())
            return (new ApiResponse)->error(null, ApiResponse::BAD_REQUEST, 'You are not a valid user type or role');


        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (User::find()->where(['email' => $form->receiver_email])->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Email already registered, invite a new email');
        }

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        if (!$model = $form->schoolInviteAdmin($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not invite school user');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionStudentParent()
    {
        $form = new InviteLog(['scenario' => InviteLog::SCENARIO_STUDENT_INVITE_PARENT]);
        $form->attributes = Yii::$app->request->post();

        if (Yii::$app->user->identity->type != 'student')
            return (new ApiResponse)->error(null, ApiResponse::BAD_REQUEST, 'You are not a valid user');

        $student = User::findOne(['id' => Yii::$app->user->id]);
        $form->sender_id = $student->id;
        $form->sender_type = $student->type;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$model = $form->studentInviteParent()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not invite parent');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionSchoolTeacher()
    {
        $form = new InviteLog(['scenario' => InviteLog::SCENARIO_SCHOOL_INVITE_TEACHER]);
        $form->attributes = Yii::$app->request->post();

        if (Yii::$app->user->identity->type != 'school')
            return (new ApiResponse)->error(null, ApiResponse::BAD_REQUEST, 'You are not a valid user');

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $form->sender_id = $school->id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$model = $form->schoolInviteTeacher()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not invite teacher');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionTeacherSchool()
    {
        $form = new InviteLog(['scenario' => InviteLog::SCENARIO_TEACHER_INVITE_SCHOOL]);
        $form->attributes = Yii::$app->request->post();

        if (Yii::$app->user->identity->type != 'teacher')
            return (new ApiResponse)->error(null, ApiResponse::BAD_REQUEST, 'You are not a valid user');

        $form->sender_id = Yii::$app->user->id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$model = $form->teacherInviteSchool()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not invite school');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionParentSchool()
    {
        $form = new InviteLog(['scenario' => InviteLog::SCENARIO_TEACHER_INVITE_SCHOOL]);
        $form->attributes = Yii::$app->request->post();

        if (Yii::$app->user->identity->type != 'parent')
            return (new ApiResponse)->error(null, ApiResponse::BAD_REQUEST, 'You are not a valid user');

        $form->sender_id = Yii::$app->user->id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$model = $form->parentInviteSchool()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not invite school');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionVerify($token)
    {
        if ($model = InviteLog::findOne(['token' => $token, 'status' => 0])) {
            if ($model->sender_type == 'school' && $model->receiver_type == 'school') {
                if(empty($model->sender_school_id)){
                    $schoolID = $model->sender_id;
                    $school = Schools::findOne(['id' => $schoolID]);
                    $schoolName = $school->name;
                    $userID = $school->user_id;
                }else{
                    $schoolName = Schools::findOne(['id' => $model->sender_school_id])->name;
                    $userID = $model->sender_id;
                }

                $name = User::findOne(['id' => $userID, 'type' => 'school']);
                return (new ApiResponse)->success(array_merge(ArrayHelper::toArray($model), ['sender_name' => $name->firstname.' '.$name->lastname, 'school_name' => $schoolName]));
            }

            return (new ApiResponse)->success($model);
        } else
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid or expired token');
    }

    public function actionSchoolParent()
    {
        $form = new InviteLog(['scenario' => InviteLog::SCENARIO_SCHOOL_INVITE_PARENT]);
        $form->attributes = Yii::$app->request->post();

        if (Yii::$app->user->identity->type != 'school')
            return (new ApiResponse)->error(null, ApiResponse::BAD_REQUEST, 'You are not a valid user');

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $form->sender_id = $school->id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$model = $form->schoolInviteParent()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not invite parent');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionVerified($token)
    {
        if (!$model = InviteLog::findOne(['token' => $token, 'status' => 0]))
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid or expired token');

        if (Yii::$app->user->identity->type != $model->receiver_type)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Your invitation does not correspond with you.');


        if ($model->sender_type == 'school' && $model->receiver_type == 'school') {
            $model->SchoolAdmin($model);
        } elseif ($model->sender_type == 'school' && $model->receiver_type == 'teacher') {
            $school_teacher = new SchoolTeachers;
            $school_teacher->teacher_id = Yii::$app->user->id;
            $school_teacher->school_id = $model->sender_school_id;
            $school_teacher->status = 1;
            if (!$school_teacher->save()) {
                return $school_teacher->errors;
            }

            if (!empty($model->receiver_class)) {
                $teacher_class = new TeacherClass;
                $teacher_class->teacher_id = Yii::$app->user->id;
                $teacher_class->school_id = $model->sender_school_id;
                $teacher_class->class_id = $model->receiver_class;
                $teacher_class->status = 1;
                if (!$teacher_class->save()) {
                    return $teacher_class->errors;
                }
            }

            //TODO Add teacher subjects to them.

            $notification = new InputNotification();
            $notification->NewNotification('teacher_accept_school_invitation_school', [['invitation_id', $model->id], ['teacher_id', Yii::$app->user->id]]);

        } elseif ($model->sender_type == 'teacher' && $model->receiver_type == 'school') {


        } elseif ($model->sender_type == 'school' && $model->receiver_type == 'parent') {
            $parent = new Parents();
            $parent->parent_id = Yii::$app->user->id;
            $parent->student_id = $model->extra_data;
            $parent->inviter = 'school';
            $parent->status = 1;
            $parent->invitation_token = $model->token;
            if (!$parent->save()) {
                return $parent->errors;
            }
        } elseif ($model->sender_type == 'student' && $model->receiver_type == 'parent') {
            $parent_model = new Parents;
            $parent_model->parent_id = Yii::$app->user->id;
            $parent_model->student_id = $model->sender_id;
            $parent_model->status = 1;
            if (!$parent_model->save()) {
                return false;
            }
        } elseif ($model->sender_type == 'school' && $model->receiver_type == 'student') {
            $modelStudent = new StudentSchool;
            $modelStudent->student_id = Yii::$app->user->id;
            $modelStudent->school_id = $model->sender_school_id;
            $modelStudent->class_id = $model->receiver_class;
            $modelStudent->invite_code = "invite-" . $model->id;
            $modelStudent->status = 1;
            if (!$modelStudent->save()) {
                return $modelStudent->errors;
            }
        } elseif ($model->sender_type == 'teacher' && $model->receiver_type == 'student') {
            $modelStudent = new StudentSchool;
            $modelStudent->student_id = Yii::$app->user->id;
            $modelStudent->school_id = $model->sender_id;
            $modelStudent->class_id = $model->receiver_class;
            $modelStudent->invite_code = "invite-" . $model->id;
            $modelStudent->status = 1;
            if (!$modelStudent->save()) {
                return $modelStudent->errors;
            }
        }
        // Pricing::ActivateStudentTrial($model->sender_id); Student trial
        $model->status = SharedConstant::VALUE_ONE;
        if (!$model->save()) {
            return false;
        }

        return (new ApiResponse)->success($model);
    }


    public function actionResend($id)
    {
        $senderID = Yii::$app->user->identity->type == 'school' ? Schools::findOne(['id' => Utility::getSchoolAccess()])->id : Yii::$app->user->id;
        if ($model = InviteLog::findOne(['id' => $id, 'status' => 0, 'sender_id' => $senderID])) {
            $model->ResendNotification($model);
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Invitation has been resent');
        } else
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not process your request');
    }

    public function actionRemove($id)
    {
        $senderID = Yii::$app->user->identity->type == 'school' ? Schools::findOne(['id' => Utility::getSchoolAccess()])->id : Yii::$app->user->id;
        if ($model = InviteLog::findOne(['id' => $id, 'status' => 0, 'sender_id' => $senderID])) {
            $model->delete();
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Invitation has been removed');
        } else
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not process your request');
    }

    public function actionDeleteSchoolInvitedUser($invite_id)
    {
        if (Yii::$app->user->identity->type != 'school') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $model = InviteLog::findOne(['id' => $invite_id, 'receiver_type' => 'school', 'sender_type' => 'school', 'sender_id' => $school->id]);

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        if (!$model->delete()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User not removed.');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'School invited user removed');
    }


    /**
     * Login action.
     *
     * @return Response|string
     */

    public function actionIndex()
    {

        $user = new User();
        $inviteLog = new InviteLog(['scenario' => InviteLog::SCENARIO_INVITE]);
        $inviteLog->attributes = \Yii::$app->request->post();

        if ($inviteLog->validate()) {

            $userId = Utility::getUserId();
            //get email
            $findUser = $user->findOne(['id' => $userId]);

            if (!empty($findUser)) {

                $checkEmailExist = $user->findByLoginDetail($findUser->email);

                try {
                    $token = rand(1, 10000000);
                    //TODO: add confirm-invite url as environment variable
                    $invitationLink = 'https://gradely.com/confirm-invite/tk?=' . $token;
                    $inviteLog->receiver_name = $this->request['receiver_name'];
                    $inviteLog->receiver_email = $this->request['receiver_email'];
                    $inviteLog->receiver_phone = $this->request['receiver_phone'];
                    $inviteLog->sender_type = $this->request['sender_type'];
                    $inviteLog->receiver_type = $this->request['receiver_type'];
                    $inviteLog->sender_id = $userId;
                    $inviteLog->token = (string)$token;
                    $inviteLog->save();
                    //sender_type e.g school, receiver type e.g parent

                    // $this->getInviteEmail($this->request['sender_type'],$this->request['receiver_type'],$invitationLink,$this->request['receiver_email']);
                    $this->getInviteEmail($this->request['receiver_type'], $invitationLink, $this->request['receiver_email']);
                    return [
                        'code' => 200,
                        'data' => $inviteLog
                    ];
                } catch (\Exception  $exception) {
                    \Sentry\captureException($exception);
                    return [
                        'code' => 200,
                        'message' => $exception->getMessage(),
                    ];
                }
            } else {

                return [
                    'code' => 200,
                    'message' => 'Invalid user please check bearer token',
                ];
            }
        }

        return $inviteLog->errors;
    }

    public function actionValidateInviteToken()
    {

        $inviteLog = new InviteLog(['scenario' => InviteLog::SCENARIO_VALIDATE_INVITE_TOKEN]);

        $inviteLog->attributes = \Yii::$app->request->post();

        if ($inviteLog->validate()) {

            $inviteLog = new InviteLog();
            $user = new User();
            $checkTokenExist = $inviteLog->findOne(['token' => $this->request['token'], 'status' => 0]);
            if (!empty($checkTokenExist)) {

                $checkUserExist = User::findOne(['email' => $checkTokenExist->receiver_email]);
                //var_dump($checkUserExist); exit;
                //teacher to school invite
                if (empty($checkUserExist) && $checkTokenExist->sender_type == 'school' && $checkTokenExist->receiver_type == 'teacher') {
                    try {
                        $schoolTeacher = new SchoolTeachers();
                        $schoolTeacher->teacher_id = $user->id;
                        $schoolTeacher->school_id = $checkTokenExist->sender_id;
                        $schoolTeacher->save();
                        return [
                            'code' => '200',
                            'message' => 'Token validated successfully',
                            'data' => $checkTokenExist
                        ];
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }

                } elseif (!empty($checkUserExist) && $checkTokenExist->sender_type == 'school' && $checkTokenExist->receiver_type == 'teacher') {
                    //if receivers email exist
                    try {
                        $checkTokenExist->status = 1;
                        $checkTokenExist->save();
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                }

                //student to parent invite
                if (empty($checkUserExist) && $checkTokenExist->sender_type == 'student' && $checkTokenExist->receiver_type == 'parent') {
                    try {
                        $studentParent = new Parents();
                        $studentParent->parent_id = $user->id;
                        $studentParent->student_id = $checkTokenExist->sender_id;
                        $studentParent->save();

                        return [
                            'code' => '200',
                            'message' => 'Token validated successfully',
                            'data' => $checkTokenExist
                        ];
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                } elseif (!empty($checkUserExist) && $checkTokenExist->sender_type == 'school' && $checkTokenExist->receiver_type == 'teacher') {
                    //if receivers email exist
                    try {
                        $checkTokenExist->status = 1;
                        $checkTokenExist->save();
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                }

                //parent to school invite
                if (empty($checkUserExist) && $checkTokenExist->sender_type == 'parent' && $checkTokenExist->receiver_type == 'school') {
                    try {
                        return [
                            'code' => '200',
                            'message' => 'Token validated successfully',
                            'data' => $checkTokenExist
                        ];
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                } elseif (!empty($checkUserExist) && $checkTokenExist->sender_type == 'school' && $checkTokenExist->receiver_type == 'teacher') {
                    //if receivers email exist
                    try {
                        $checkTokenExist->status = 1;
                        $checkTokenExist->save();
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                }

                //school to parent invite
                if (empty($checkUserExist) && $checkTokenExist->sender_type == 'school' && $checkTokenExist->receiver_type == 'parent') {
                    try {
                        return [
                            'code' => '200',
                            'message' => 'Token validated successfully',
                            'data' => $checkTokenExist
                        ];
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                } elseif (!empty($checkUserExist) && $checkTokenExist->sender_type = 'school' && $checkTokenExist->receiver_type = 'teacher') {
                    //if receivers email exist
                    try {
                        $checkTokenExist->status = 1;
                        $checkTokenExist->save();
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                }

                //student to school invite
                if (empty($checkUserExist) && $checkTokenExist->sender_type == 'student' && $checkTokenExist->receiver_type == 'school') {
                    try {
                        $studentSchool = new StudentSchool();
                        $studentSchool->teacher_id = $user->id;
                        $studentSchool->school_id = $checkTokenExist->sender_id;
                        $studentSchool->save();

                        return [
                            'code' => '200',
                            'message' => 'Token validated successfully',
                            'data' => $checkTokenExist
                        ];
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                } elseif (!empty($checkUserExist) && $checkTokenExist->sender_type = 'school' && $checkTokenExist->receiver_type = 'teacher') {
                    //if receivers email exist
                    try {
                        $checkTokenExist->status = 1;
                        $checkTokenExist->save();
                    } catch (\Exception $exception) {
                        \Sentry\captureException($exception);
                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                }
            }
        }
        return $inviteLog->errors;
    }

    private function getInviteEmail($receiverType, $invitationLink, $receiverEmail)
    {
        Yii::$app->mailer->compose()
            ->setFrom(Yii::$app->params['invitationSentFromEmail'])
            ->setTo($receiverEmail)
            ->setSubject(Yii::$app->params['invitationEmailSubject'])
            ->setHtmlBody(Yii::$app->params['invitationEmailBody'] . $invitationLink)
            ->send();
        return;
    }


}