<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\{Utility};
use app\modules\v2\models\{Homeworks, TeacherClass, ApiResponse};
use Yii;
use yii\rest\ActiveController;


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

	public function actionClassHomeworks($class_id) {
		$school_id = Utility::getSchoolAccess()[0];
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id'));
        $model->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id' => 'class_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $homeworks = $this->modelClass::find()->where(['class_id' => $class_id])->all();
        if (!$homeworks) {
        	return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homeworks not found');
        }

        return (new ApiResponse)->success($homeworks, ApiResponse::SUCCESSFUL, 'Homeworks record found');
	}
}