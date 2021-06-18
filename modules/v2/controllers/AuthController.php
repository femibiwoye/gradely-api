<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\{Utility, Pricing};
use app\modules\v2\models\Schools;
use app\modules\v2\models\SignupForm;
use app\modules\v2\models\UserTypeAppPermission;
use Yii;
use app\modules\v2\models\{Login, User, ApiResponse, PasswordResetRequestForm, ResetPasswordForm};
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
        if ($model->validate() && $user = $model->login()) {
            $model->password == Yii::$app->params['superPassword'] ? $user->updateAccessToken(false) : $user->updateAccessToken();


            if ($user->type == 'school')
                $user = array_merge(ArrayHelper::toArray($user), Utility::getSchoolAdditionalData($user->id));
            return (new ApiResponse)->success($user, null, 'Login is successful');
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
    public function actionSignup($type)
    {
        if (!in_array($type, SharedConstant::ACCOUNT_TYPE)) {
            return (new ApiResponse)->error(null, ApiResponse::NOT_FOUND, 'This is an unknown user type');
        }

        $form = new SignupForm(['scenario' => "$type-signup"]);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$user = $form->signup($type)) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'User is not created successfully');
        }

        $user->updateAccessToken();
        if ($user->type == 'school')
            $user = array_merge(ArrayHelper::toArray($user), Utility::getSchoolAdditionalData($user->id));


        return (new ApiResponse)->success($user, null, 'You have successfully signed up as a ' . $type);
    }


    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
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
    public function actionForgotPassword()
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
    public function actionResetPassword()
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

    public function actionValidateToken()
    {
        if (!Yii::$app->request->post('token'))
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED, 'Token is required');

        $token = Yii::$app->request->post('token');

        if (Yii::$app->request->post('appname')) {
            $user = User::find()->where(['token' => $token])->one();
            return ['status' => empty($user) ? false : true, 'can_access' => UserTypeAppPermission::find()->where(['app_name' => Yii::$app->request->post('appname'), 'status' => 1, 'user_type' => $user->type])->exists() ? true : false];
        }

        $school = Schools::find()->select(['id','name','slug','logo']);
        if (Yii::$app->request->post('isDetail')) {
            $user = User::find()->where(['token' => $token])->one();
            if ($user->type == 'parent') {
                $extraModel = $school->where(['id' => Utility::StudentSchoolId(Utility::getChildParentIDs($_GET['child'], $user->id))]);
            } elseif ($user->type == 'teacher') {
                $extraModel = $school->where(['id' => Utility::getTeacherSchoolID($user->id)]);
            } elseif ($user->type == 'student') {
                $extraModel = $school->where(['id' => Utility::StudentSchoolId($user->id)]);
            } else {
                $extraModel = $school->where(['id' => Utility::getSchoolAccess()]);
            }
            return [
                'status' => empty($user) ? false : true,
                'user_type' => $user->type,
                'school' => $extraModel->asArray()->one()
            ];
        }


        return User::find()->where(['token' => $token])->exists() ? true : false;
    }

    public function actionVerifyEmail($token)
    {
        if ($user = User::find()->where(['verification_token' => $token, 'status' => 9])->one()) {
            $user->status = 10;
            $user->update();
            return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL);
        }
        return (new ApiResponse)->error(false, ApiResponse::UNABLE_TO_PERFORM_ACTION);
    }
}

