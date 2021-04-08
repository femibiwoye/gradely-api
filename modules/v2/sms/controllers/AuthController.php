<?php

namespace app\modules\v2\sms\controllers;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\SmsAuthentication;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\SignupForm;
use Yii;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


class AuthController extends ActiveController
{
    public $modelClass = 'app\modules\v2\sms\models\Schools';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
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

    public function beforeAction($action)
    {
        if (!SmsAuthentication::checkStatus()) {
            $this->asJson(\Yii::$app->params['customError401']);
            return false;
        }
        return parent::beforeAction($action);
    }


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
}