<?php

namespace app\modules\v2\controllers;

use Yii;
use yii\filters\{AccessControl, VerbFilter, ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\modules\v2\models\{Schools, Login, User, StudentSchool, SchoolTeachers, Parents, UserProfile, ApiResponse, PasswordResetRequestForm, ResetPasswordForm};
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, HttpBasicAuth, CompositeAuth, QueryParamAuth};
use app\modules\v2\helpers\Utility;


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
                HttpBasicAuth::className(),
                HttpBearerAuth::className(),
                QueryParamAuth::className(),
            ],
            'except' => ['options', 'reset-password', 'forgot-password', 'login'],
        ];

        return $behaviors;
    }

    public function actions() {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
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
        $model = new $this->modelClass;
        if (!$model->resetAccessToken()) {
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED);
        }

        return (new ApiResponse)->success(["access_token" => ""]);
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
            return (new ApiResponse)->error(['form' => $form->getErrors()], ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$form->resetPassword()) {
            return (new ApiResponse)->error(null, ApiResponse::UNAUTHORIZED);
        }

        return (new ApiResponse)->success(["access_token" => $form->getNewAccessToken()]);
    }
}

