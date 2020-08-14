<?php

namespace app\modules\v2\student\controllers;

use Yii;
use yii\rest\ActiveController;
use app\modules\v2\models\{Parents, ApiResponse};
use app\modules\v2\components\CustomHttpBearerAuth;

class ProfileController extends ActiveController
{
	public $modelClass = 'app\modules\v2\models\UserModel';

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
		unset($actions['create']);
		unset($actions['update']);
		unset($actions['delete']);
		unset($actions['index']);
		unset($actions['view']);
		return $actions;
	}

	public function actionParents()
	{
		$models = Parents::find()
					->where(['student_id' => Yii::$app->user->id])
					->all();

		if (!$models) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Parents record not found');
		}

		return (new ApiResponse)->success($models, ApiResponse::SUCCESSFUL, 'Parents record found');
	}
}

