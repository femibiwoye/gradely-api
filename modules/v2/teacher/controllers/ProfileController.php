<?php

namespace app\modules\v2\teacher\controllers;

use Yii;
use app\modules\v2\models\{User, ApiResponse};
use app\modules\v2\teacher\models\{TeacherUpdateEmailForm, TeacherUpdatePasswordForm, UpdateTeacherForm};
use app\modules\v2\components\{SharedConstant};
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};


/**
 * Auth controller
 */
class ProfileController extends ActiveController
{
	public $modelClass = 'app\modules\v2\models\User';

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

	public function actionUpdateEmail() {
		$model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();
		if ($model->type != SharedConstant::TYPE_TEACHER || !$model) {
			return  (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher not found!');
		}

		$form = new TeacherUpdateEmailForm;
		$form->attributes = Yii::$app->request->post();
		$form->user = $model;
		if (!$form->validate()) {
			return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		$model->attributes = $form->attributes;
		if (!$form->sendEmail() || !$model->save()) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION,'Email is not updated!');
		}

		return (new ApiResponse)->success($model);
	}

	public function actionUpdatePassword() {
		$model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();
		if ($model->type != SharedConstant::TYPE_TEACHER || !$model) {
			return  (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher not found!');
		}

		$form = new TeacherUpdatePasswordForm;
		$form->attributes = Yii::$app->request->post();
		$form->user = $model;
		if (!$form->validate()) {
			return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		if (!$form->updatePassword()) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION,'Password is not updated!');
		}

		return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Password is successfully updated!');
	}

	public function actionUpdate() {
		$model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();
		if ($model->type != SharedConstant::TYPE_TEACHER || !$model) {
			return  (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher not found!');
		}

		$form = new UpdateTeacherForm;
		$form->attributes = Yii::$app->request->post();
		$form->user = $model;
		if (!$form->validate()) {
			return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		if (!$model = $form->updateTeacher()) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher is not updated!');
		}

		return (new ApiResponse)->success($model);
	}
}

