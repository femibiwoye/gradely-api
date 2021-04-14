<?php

namespace app\modules\v2\sms\controllers;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\User;
use yii\rest\ActiveController;


class ValidateController extends ActiveController
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

    public function actionValidate($token)
    {
        if ($user = User::find()->where(['token' => $token])->andWhere(['<>', 'status', 0])->one()) {
            return (new ApiResponse)->success($user);
        }
        return (new ApiResponse)->error(null, ApiResponse::NON_AUTHORITATIVE, 'You provided invalid login details');
    }

}