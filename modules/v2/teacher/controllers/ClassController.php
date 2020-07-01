<?php

namespace app\modules\v2\teacher\controllers;

use Yii;
use app\modules\v2\models\{Classes, ApiResponse, TeacherClass};
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};

/**
 * ClassController implements the CRUD actions for Classes model.
 */
class ClassController extends ActiveController
{
	/**
	 * {@inheritdoc}
	 */
	public $modelClass = 'app\modules\v2\models\Classes';

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

	/**
	 * Displays a single Classes model.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView($code)
	{
		$class = $this->modelClass::findOne(['class_code' => $code]);
		if (!$class) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found!');
		}

		return (new ApiResponse)->success($class, ApiResponse::SUCCESSFUL, 'Class found'); 
	}

	public function actionAddTeacher()
	{
		$class = $this->modelClass::findOne(['class_code' => Yii::$app->request->post('code')]);
		if (!$class) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found!');
		}

		if (!$class->school) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'School not found!');
		}

		$model = new TeacherClass;
		$model->teacher_id = Yii::$app->user->id;
		$model->school_id = $class->school->id;
		$model->class_id = $class->id;
		if (!$model->save()) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher is not successfully added!');
		}

		return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Teacher added successfully');
	}

	public function actionSchool($id)
	{
		$classes = $this->modelClass::find()
					->where(['school_id' => $id])
					->all();

		if (!$classes) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes not found!');
		}

		return (new ApiResponse)->success($classes, ApiResponse::SUCCESSFUL, 'Classes found');
	}
}
