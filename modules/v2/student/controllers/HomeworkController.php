<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\models\{Homeworks, ApiResponse};

use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use app\modules\v2\components\SharedConstant;


/**
 * Schools/Parent controller
 */
class HomeworkController extends ActiveController
{
	public $modelClass = 'app\modules\v2\models\Homeworks';

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

	public function actionCompletedHomeworks()
	{
		$models = $this->modelClass::find()
					->innerJoin('quiz_summary', 'quiz_summary.homework_id = homeworks.id')
					->where(['quiz_summary.student_id' => Yii::$app->user->id, 'homeworks.type' => 'homework', 'quiz_summary.submit' => SharedConstant::VALUE_ONE]);

		if (!$models) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
		}

		$provider = new ActiveDataProvider([
            'query' => $models,
            'pagination' => [
                'pageSize' => 10,
                'validatePage' => false,
            ],
        ]);

		return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found');
	}
}