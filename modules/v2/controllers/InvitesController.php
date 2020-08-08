<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\SchoolRole;
use Yii;
use yii\filters\{AccessControl, VerbFilter, ContentNegotiator};
use yii\filters\auth\CompositeAuth;
use yii\web\Controller;
use yii\web\Response;
use app\modules\v2\models\{Schools, User, InviteLog, SchoolTeachers, Parents};
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
            'except' => ['validate-invite-token'],
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        if (!$model = $form->schoolInviteAdmin($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not invite school user');
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model = $form->schoolInviteTeacher()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not invite teacher');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionVerify($token)
    {
        if ($model = InviteLog::findOne(['token' => $token, 'status' => 0]))
            return (new ApiResponse)->success($model);
        else
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid or expired token');
    }

    public function actionVerified($token)
    {
        if (!$model = InviteLog::findOne(['token' => $token, 'status' => 0]))
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid or expired token');


        if ($model->sender_type == 'school' && $model->receiver_type == 'school') {

        } elseif ($model->sender_type == 'school' && $model->receiver_type == 'teacher') {

        }elseif ($model->sender_type == 'teacher' && $model->receiver_type == 'school') {

        }elseif ($model->sender_type == 'student' && $model->receiver_type == 'parent') {

        }


        return (new ApiResponse)->success($model);

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
                } catch (Exception  $exception) {

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
                    } catch (Exception $exception) {
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
                    } catch (Exception $exception) {
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
                    } catch (Exception $exception) {
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
                    } catch (Exception $exception) {
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
                    } catch (Exception $exception) {
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
                    } catch (Exception $exception) {
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
                    } catch (Exception $exception) {
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
                    } catch (Exception $exception) {
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
                    } catch (Exception $exception) {
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
                    } catch (Exception $exception) {
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