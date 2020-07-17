<?php

namespace app\modules\v2\teacher\controllers;

use Yii;
use app\modules\v2\models\{Homeworks, Classes, ApiResponse};
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};

class HomeworkController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Homeworks';

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

    public function actionClassHomeworks($class_id) {
        $model = Classes::findOne(['id' => $class_id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class record not found');
        }

        return (new ApiResponse)->success($model->homeworks ? $model->homeworks : $model, ApiResponse::SUCCESSFUL, 'Class record found');
    }

    public function actionHomework($homework_id) {
        $model = $this->modelClass::findOne(['id' => $homework_id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework record found');
    }
}
