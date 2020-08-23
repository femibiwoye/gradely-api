<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\models\notifications\InappNotification;
use Yii;
use yii\rest\ActiveController;
use app\modules\v2\models\{SecurityQuestions, ApiResponse, SecurityQuestionAnswer};


/**
 * Schools/Parent controller
 */
class GeneralController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\User';

    /**
     * @return array
     */
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
            'class' => CustomHttpBearerAuth::className(),
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['view']);
        return $actions;
    }

    public function actionSecurityQuestions()
    {
        $models = SecurityQuestions::find()->all();
        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($models, ApiResponse::SUCCESSFUL, 'Record not found');
    }

    public function actionSetSecurityQuestion()
    {
        if (!$model = SecurityQuestionAnswer::findOne(['user_id' => Yii::$app->user->id]))
            $model = new SecurityQuestionAnswer;
        $model->question = Yii::$app->request->post('question');
        $model->user_id = Yii::$app->user->id;
        $model->answer = Yii::$app->request->post('answer');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Data not validated');
        }

        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record saved');
    }

    public function actionUpdateSecurityQuestion()
    {

        if (!$model = SecurityQuestionAnswer::findOne(['user_id' => Yii::$app->user->id]))
            if (!$model) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
            }

        $model->answer = Yii::$app->request->post('answer');
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Answer not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Answer updated');
    }
}