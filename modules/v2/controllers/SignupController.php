<?php

namespace app\modules\v2\controllers;

use Yii;
use yii\rest\ActiveController;
use app\modules\v2\models\{SignupForm, ApiResponse, SharedConstant};

use yii\filters\auth\{HttpBearerAuth, CompositeAuth};


class SignupController extends ActiveController {
	public $modelClass = "app\modules\v2\models\User";
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

			'except' => ['options', 'create'],
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

	public function actionCreate() {
		$form = new SignupForm;
		$form->attributes = Yii::$app->request->post();
		$form->country_code = SharedConstant::COUNTRY_CODE;
		if (!$form->validate()) {
			return (new ApiResponse)->error([$form->getErrors()], ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		if (!$user = $form->signup()) {
			return $value;
		}

		$user->updateAccessToken();
		return true;
	}
}
