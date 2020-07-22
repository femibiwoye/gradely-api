<?php

namespace app\modules\v2\teacher\controllers;

use Yii;
use app\modules\v2\models\{Homeworks, Classes, ApiResponse};
use app\modules\v2\components\SharedConstant;
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
        $model = $this->modelClass::find()->where(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id])->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework record found');
    }

    public function actionDeleteHomework($homework_id) {
        $model = $this->modelClass::findOne(['id' => $homework_id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        $model->status = SharedConstant::STATUS_DELETED;
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not deleted');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Homework record deleted');
    }

    public function actionExtendDate($homework_id) {
        $close_date = Yii::$app->request->post('close_date');
        $model = Homeworks::find()->where(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id])->one();
        if (!$model || ($model->teacher_id != Yii::$app->user->id)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        if (strtotime($close_date) <= time()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Close date should not be in the past.');
        }

        $model->close_date = $close_date;
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework date not update');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework close date updated');
    }
}
