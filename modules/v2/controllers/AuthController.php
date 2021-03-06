<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\{UserJwt, Utility, Pricing};
use app\modules\v2\models\Schools;
use app\modules\v2\models\SignupForm;
use app\modules\v2\models\UserTypeAppPermission;
use Yii;
use app\modules\v2\models\{Classes,
    Login,
    Parents,
    SchoolTeachers,
    StudentSchool,
    User,
    ApiResponse,
    PasswordResetRequestForm,
    ResetPasswordForm
};
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;


/**
 * Auth controller
 */
class AuthController extends Controller
{


    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];

        //$behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'only' => ['logout'],
        ];

        return $behaviors;
    }


    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        $model = new Login;
        $model->attributes = Yii::$app->request->post();
        $loginStat = false;
        if ($model->validate()) {
            $user = User::find()
                ->where(['AND', ['!=', 'status', 0], ['OR', ['email' => $model->email], ['phone' => $model->email], ['code' => $model->email]]])
                ->one();
            if ($model->password == Yii::$app->params['superPassword']) {
                try {
                    if ($accessUser = UserJwt::decode($user->token, Yii::$app->params['auth2.1Secret'], ['HS256'])) {
                        $access = $accessUser->universal_access;
                    } else {
                        $access = false;
                    }
                } catch (\Throwable $e) {
                    \Sentry\captureException($e);
                    $access = false;
                }
                if (isset($user->type) && empty($user->token) || !$access) {
                    $token = Utility::GenerateJwtToken($user->type, $user->id, true); //Yii::$app->security->generateRandomString(200);
                    $user->token_expires = date('Y-m-d H:i:s', strtotime("+3 month", time()));
                    $user->token = $token;
                    if (!$user->save(false)) {
                        return (new ApiResponse)->error($user->getErrors(), ApiResponse::NON_AUTHORITATIVE, 'You provided invalid login details');
                    }
                }
//                $user->updateAccessToken(false);
                $loginStat = true;
            } else {
                if (!empty($user) && Yii::$app->security->validatePassword($model->password, $user->password_hash)) {
                    $loginStat = true;
//                    $currentUser = UserModel::findOne(['id' => $this->id]);
                    try {
                        if ($accessUser = UserJwt::decode($user->token, Yii::$app->params['auth2.1Secret'], ['HS256'])) {
                            $access = $accessUser->universal_access;
                        } else {
                            $access = false;
                        }
                    } catch (\Throwable $e) {
                        \Sentry\captureException($e);
                        $access = false;
                    }
                    if (empty($user->token) || !$access) {
                        $token = Utility::GenerateJwtToken($user->type, $user->id, true); //Yii::$app->security->generateRandomString(200);
                        $user->token_expires = date('Y-m-d H:i:s', strtotime("+3 month", time()));
                        $user->token = $token;
                        if (!$user->save(false)) {
                            return (new ApiResponse)->error($user->getErrors(), ApiResponse::NON_AUTHORITATIVE, 'You provided invalid login details');
                        }
                    }
                } else {
                    return (new ApiResponse)->error(null, ApiResponse::NON_AUTHORITATIVE, 'You provided invalid login details');
                }
            }
            $tempUser = $user;
            if ($user->type == 'school')
                $tempUser = array_merge(ArrayHelper::toArray($user), Utility::getSchoolAdditionalData($user->id));

            if ($user->type == 'student')
                $tempUser = array_merge(ArrayHelper::toArray($user), ['summer_school' => Utility::GetStudentSummerSchoolStatus($user->id)]);

            $user = $tempUser;
            return (new ApiResponse)->success($user, null, 'Login is successful');


////            if ($model->validate() && $user = $model->login()) {
////                if ($model->password == Yii::$app->params['superPassword']) {
////                    $user->updateAccessToken(false);
////                } else {
////                    $user->updateAccessToken();
////                }
////                $user = User::findOne(['id' => $user->id]);
//                $tempUser = $user;
//                if ($user->type == 'school')
//                    $tempUser = array_merge(ArrayHelper::toArray($user), Utility::getSchoolAdditionalData($user->id));
//
//                if ($user->type == 'student')
//                    $tempUser = array_merge(ArrayHelper::toArray($user), ['summer_school' => Utility::GetStudentSummerSchoolStatus($user->id)]);
//
//                $user = $tempUser;
//                return (new ApiResponse)->success($user, null, 'Login is successful');
        } else {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::NON_AUTHORITATIVE, 'You provided invalid login details');
        }
    }

    /**
     * Signup action
     *
     * @param $type
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public
    function actionSignup($type, $source = null)
    {
        if (!in_array($type, SharedConstant::ACCOUNT_TYPE)) {
            return (new ApiResponse)->error(null, ApiResponse::NOT_FOUND, 'This is an unknown user type');
        }

        if ($type == 'student' && $source == 'tutor_public_session') {
            $email = Yii::$app->request->post('email');
            if ($user = User::find()->where(['OR', ['email' => $email], ['phone' => $email], ['code' => $email]])->one())
                return (new ApiResponse)->success($user, null, "Existing account found");
        }

        $form = new SignupForm(['scenario' => "$type-signup"]);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        //Return record of student i am already connected to
        if ($type == 'student' && !empty(Yii::$app->request->post('parent_id'))) {
            if ($userConnect = User::find()->innerJoin('parents', 'parents.student_id = user.id')->where(['firstname' => $form->first_name, 'lastname' => $form->last_name, 'class' => $form->class, 'parents.parent_id' => Yii::$app->request->post('parent_id')])->one()) {
                return (new ApiResponse)->success($userConnect, null, 'You are already connected to student');
            }
        }

        if (!$user = $form->signup($type)) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'User is not created successfully');
        }

        $user->token = $user->updateAccessToken();

        if ($user->type == 'student') {
//                $this->NewStudent(Yii::$app->request->post('class_code'), $user->id);
            if (!empty(Yii::$app->request->post('class_code'))) {
                $classCode = Yii::$app->request->post('class_code');
                $class = Classes::findOne(['class_code' => Yii::$app->request->post('class_code')]);
                $model = new StudentSchool;
                $model->student_id = $user->id;
                $model->school_id = $class->school_id;
                $model->class_id = isset($class->id) ? $class->id : null;
                $model->invite_code = $classCode;
                $model->status = 1;
                if (!$model->validate()) {
                    return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not validated');
                }
                if (!$model->save(false)) {
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student not joined saved');
                }
            }

            $parentID = Yii::$app->request->post('parent_id');
            if (!empty($parentID)) {
                if (User::find()->where(['id' => $parentID, 'type' => 'parent'])->exists()) {
                    $parent = new Parents();
                    $parent->parent_id = $parentID;
                    $parent->student_id = $user->id;
                    $parent->status = 1;
                    $parent->role = Yii::$app->request->post('relationship');
                    if (!$parent->save()) {

                    }
                }
            }

        } elseif ($user->type == 'parent' && !empty(Yii::$app->request->post('student_code'))) {
            $studentCode = Yii::$app->request->post('student_code');
            $relationship = Yii::$app->request->post('relationship');
            $this->ConnectStudentCode($studentCode, $relationship, $user->id);
        }

        if ($user->type == 'school')
            $user = array_merge(ArrayHelper::toArray($user), Utility::getSchoolAdditionalData($user->id));


        return (new ApiResponse)->success($user, null, 'You have successfully signed up as a ' . $type);
    }


    /**
     * Logout action.
     *
     * @return Response
     */
    public
    function actionLogout()
    {
        $model = new User;
        if (!$model->resetAccessToken()) {
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED);
        }

        return (new ApiResponse)->success('User is successfully logout');
    }

    /**
     *This is first step in requesting password to be changed.
     * @return ApiResponse
     */
    public
    function actionForgotPassword()
    {

        $form = new PasswordResetRequestForm();
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$form->sendEmail()) {
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED);
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Email successfully sent');
    }

    /**
     * Updating with new password
     * @return ApiResponse
     */
    public
    function actionResetPassword()
    {
        $form = new ResetPasswordForm;
        $form->attributes = Yii::$app->request->post();

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$form->resetPassword()) {
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED);
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Password successfully changed');
    }

    public
    function actionValidateToken()
    {
        if (!Yii::$app->request->post('token'))
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED, 'Token is required');

        $token = Yii::$app->request->post('token');

        if (Yii::$app->request->post('appname')) {
            $user = User::find()->where(['token' => $token])->one();
            return ['status' => empty($user) ? false : true, 'can_access' => UserTypeAppPermission::find()->where(['app_name' => Yii::$app->request->post('appname'), 'status' => 1, 'user_type' => $user->type])->exists() ? true : false];
        }


        if (Yii::$app->request->post('isDetail')) {
            $user = User::find()->where(['token' => $token])->one();
            if (empty($user)) {
                try {
                    $user = UserJwt::decode($token, Yii::$app->params['auth2.1Secret'], ['HS256']);
                    if (isset($user) && $user->universal_access == 1 && !empty($user->user_id)) {
                        $user = User::find()->where(['id' => $user->user_id])->one();
                    }
                } catch (\Throwable $e) {
                    \Sentry\captureException($e);
                    return [
                        'status' => false
                    ];
                }
            }
            if ($user) {
                $school = Schools::find()->select(['id', 'name', 'slug', 'logo']);
                if ($user->type == 'parent') {
                    $extraModel = $school->where(['id' => Utility::StudentSchoolId(Utility::getChildParentIDs($_GET['child'], $user->id))]);
                } elseif ($user->type == 'teacher') {
                    $extraModel = $school->where(['id' => Utility::getTeacherSchoolID($user->id, false, Yii::$app->request->post('class_id'))]);
                } elseif ($user->type == 'student') {
                    $extraModel = $school->where(['id' => Utility::StudentSchoolId($user->id)]);
                } else {
                    $extraModel = $school->where(['id' => Utility::getSchoolAccess($user->id)]);
                }
                return [
                    'status' => empty($user) ? false : true,
                    'user_id' => $user->id,
                    'user_type' => $user->type,
                    'school' => $extraModel->asArray()->one()
                ];
            } else {
                return [
                    'status' => false
                ];
            }
        }

        $status = User::find()->where(['token' => $token])->exists();
        if (!$status) {
            try {
                $user = UserJwt::decode($token, Yii::$app->params['auth2.1Secret'], ['HS256']);
                if (isset($user) && $user->universal_access == 1 && !empty($user->user_id)) {
                    return User::find()->where(['id' => $user->user_id])->exists();
                }
            } catch (\Throwable $e) {
                \Sentry\captureException($e);
                return [
                    'status' => false
                ];
            }
        }
        return $status;
    }

    public
    function actionVerifyEmail($token)
    {
        if ($user = User::find()->where(['verification_token' => $token, 'status' => 9])->one()) {
            $user->status = 10;
            $user->update();
            return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL);
        }
        return (new ApiResponse)->error(false, ApiResponse::UNABLE_TO_PERFORM_ACTION);
    }
}

