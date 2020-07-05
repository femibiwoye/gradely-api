<?php

namespace app\modules\v2\teacher\controllers;

use Yii;
use app\modules\v2\models\{Classes, ApiResponse, TeacherClass, User, SearchSchool};
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\components\SharedConstant;
use app\modules\v2\teacher\models\{TeacherSchoolForm, StudentClassForm, DeleteStudentForm, AddStudentForm};

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

	public function actionTeacherClass() {
		$teacher = User::find()
					->where(['id' => Yii::$app->user->id])
					->andWhere(['type' => SharedConstant::TYPE_TEACHER])
					->one();

		if (!$teacher || !$teacher->classes) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes not found!');
		}

		return (new ApiResponse)->success($teacher->classes, ApiResponse::SUCCESSFUL, 'Classes found');
	}

	public function actionSearchSchool($q) {
		$school = SearchSchool::find()->where(['like', 'name', $q])->one();
		if (!$school) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'School record not found!');
		}

		return (new ApiResponse)->success($school, ApiResponse::SUCCESSFUL, 'School record found');
	}

	public function actionAddTeacherSchool() {
		$form = new TeacherSchoolForm;
		$form->attributes = Yii::$app->request->post();
		$form->teacher_id = Yii::$app->user->id;
		if (!$form->validate()) {
			return (new ApiResponse)->error([$form->getErrors()], ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		if (!$model = $form->addTeacherClass()) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not added!');
		}

		return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record added');
	}

	public function actionStudentsInClass($class_id) {
		$form = new StudentClassForm;
		$form->class_id = $class_id;
		$form->teacher_id = Yii::$app->user->id;
		if (!$form->validate()) {
			return (new ApiResponse)->error([$form->getErrors()], ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		if (!$data = $form->getStudents()) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Records not found');
		}

		return (new ApiResponse)->success($data, ApiResponse::SUCCESSFUL, 'Record found');
	}

	public function actionDeleteStudent($student_id, $class_id)
	{
		$form = new DeleteStudentForm;
		$form->teacher_id = Yii::$app->user->id;
		$form->student_id = $student_id;
		$form->class_id = $class_id;
		if (!$form->validate()) {
			return (new ApiResponse)->error([$form->getErrors()], ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		if (!$form->deleteStudent()) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Records not deleted');
		}

		return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Record deleted');
	}

	public function actionAddStudent()
	{
		$form = new AddStudentForm;
		$form->attributes = Yii::$app->request->post();
		if (!$form->validate())
		{
			return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		if (!$user = $form->addStudents(SharedConstant::TYPE_STUDENT)) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not added');
		}

		return (new ApiResponse)->success($user, null, 'You have successfully added students');
	}
}
