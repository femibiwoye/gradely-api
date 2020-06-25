<?php

namespace app\modules\v2\controllers;

use Yii;
use yii\web\Controller;
use app\modules\v2\models\{Login, User, ApiResponse, PasswordResetRequestForm, ResetPasswordForm};
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};


/**
 * Auth controller
 */
class AuthController extends ActiveController {
    public $modelClass = 'app\modules\v2\models\User';

    public function behaviors() {
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
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
            'except' => ['options', 'reset-password', 'forgot-password', 'login'],
        ];
        return $behaviors;
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin() {
        $model = new Login;
        $model->attributes = Yii::$app->request->post();
        if ($model->validate() && $user = $model->login()) {
            return (new ApiResponse)->success(["user" => $user]);
        } else {
            return (new ApiResponse)->error(['model' => $model->getErrors()], ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout() {
        $model = new User;
        if (!$model->resetAccessToken()) {
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED);
        }

        return (new ApiResponse)->success('User is successfully logout');
    }

    public function actionForgotPassword() {
        $form = new PasswordResetRequestForm();
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error(['form' => $form->getErrors()], ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$form->sendEmail()) {
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED);
        }

        return (new ApiResponse)->success(ApiResponse::SUCCESSFULL);
    }

    public function actionResetPassword() {
        $form = new ResetPasswordForm;
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$form->resetPassword()) {
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED);
        }

        return (new ApiResponse)->success(["access_token" => $form->getNewAccessToken()]);
    }
}

