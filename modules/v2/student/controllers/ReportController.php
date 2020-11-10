<?php

namespace app\modules\v2\student\controllers;

use Yii;
use app\modules\v2\models\{ApiResponse, StudentAdditiionalTopics};
use app\modules\v2\components\SharedConstant;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};

class ReportController extends ActiveController
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

    public function actionCreateAdditionalTopic()
    {
        if (Yii::$app->user->identity->type != 'parent' || Yii::$app->user->identity->type != 'student') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $model = new StudentAdditiionalTopics;
        $model->attributes = Yii::$app->request->post();
        $model->updated_by = Yii::$app->user->id;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed!');
        }

        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record saved');
    }

    public function actionRemoveAdditionalTopic()
    {
        if (Yii::$app->user->identity->type != 'parent' || Yii::$app->user->identity->type != 'student') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $topic_id = Yii::$app->request->get('topic_id');
        $student_id = Yii::$app->request->get('student_id');

        $form = new \yii\base\DynamicModel(compact('topic_id', 'student_id'));
        $form->addRule(['topic_id', 'student_id'], 'required');;

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = StudentAdditiionalTopics::find([
            'topic_id' => $topic_id,
            'student_id' => $student_id,
            'status' => SharedConstant::VALUE_ONE
        ])->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model->status = SharedConstant::VALUE_ZERO;
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not deleted!');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record deleted');
    }
}
