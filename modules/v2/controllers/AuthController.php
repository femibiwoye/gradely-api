<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\SignupForm;
use Yii;
use app\modules\v2\models\{Login, User, ApiResponse, PasswordResetRequestForm, ResetPasswordForm};
use yii\rest\Controller;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};


/**
 * Auth controller
 */
class AuthController extends Controller
{
    public $modelClass = 'app\modules\v2\models\User';

//    public function behaviors()
//    {
//        $behaviors = parent::behaviors();
//
//        //For CORS
//        $auth = $behaviors['authenticator'];
//        unset($behaviors['authenticator']);
//        $behaviors['corsFilter'] = [
//            'class' => \yii\filters\Cors::className(),
//        ];
//        $behaviors['authenticator'] = $auth;
//        $behaviors['authenticator'] = [
//            'class' => CompositeAuth::className(),
//            'authMethods' => [
//                HttpBearerAuth::className(),
//            ],
//            'only' => ['logout'],
//        ];
//
//        return $behaviors;
//    }


    /**
     * List of allowed domains.
     * Note: Restriction works only for AJAX (using CORS, is not secure).
     *
     * @return array List of domains, that can access to this API
     */
    public static function allowedDomains()
    {
        return [
            '*',                        // star allows all domains
//            'http://test1.example.com',
//            'http://test2.example.com',
        ];
    }


    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            [
                // restrict access to domains:
                'Origin' => static::allowedDomains(),
                'Access-Control-Request-Method' => ['POST','GET'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,                 // Cache (seconds)
            ],
        ];

        // re-add authentication filter
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

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
            $user->updateAccessToken();
            return (new ApiResponse)->success($user, null, 'Login is successful');
        } else {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
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
            return (new ApiResponse)->error(null, ApiResponse::UNKNOWN, 'This is an unknown user type');
        }

        $form = new SignupForm(['scenario' => "$type-signup"]);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$user = $form->signup($type)) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User is not created successfully');
        }

        $user->updateAccessToken();
        return (new ApiResponse)->success($user, null, 'You have successfully signed up as a' . $type);
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$form->resetPassword()) {
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED);
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Password successfully changed');
    }
}

