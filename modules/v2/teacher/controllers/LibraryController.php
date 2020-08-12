<?php

namespace app\modules\v2\teacher\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\models\{TeacherClass, ApiResponse, Feed, Homeworks, User};
use app\modules\v2\teacher\models\HomeworkSummary;
use app\modules\v2\components\SharedConstant;

class LibraryController extends ActiveController
{
	public $modelClass = 'app\modules\v2\models\PracticeMaterial';

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

	public function actionIndex() {
		$class_id = Yii::$app->request->get('class_id');
		$format = Yii::$app->request->get('format');
		$date = Yii::$app->request->get('date');
		$sort = Yii::$app->request->get('sort');
		$teacher_id = Yii::$app->user->id;
		$model = new \yii\base\DynamicModel(compact('class_id', 'teacher_id'));
		$model->addRule(['class_id', 'teacher_id'], 'integer')
			->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id']]);

		if (!$model->validate()) {
			return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		$model = $this->modelClass::find()
					->innerJoin('homeworks', 'homeworks.teacher_id = practice_material.user_id')
					->andWhere(['practice_material.type' => SharedConstant::PRACTICE_TYPES[1]]);

		if ($class_id) {
			$model = $model->andWhere(['homeworks.class_id' => $class_id]);

		}

		if ($format) {
			$model = $model->andWhere(['extension' => $format]);
		}

		if ($date) {
			$model = $model->andWhere('practice_material.created_at >= :date_value', [':date_value' => $date]);
		}

		if ($sort) {
			if ($sort == 'newest') {
				$model = $model->orderBy(['created_at' => SORT_DESC]);
			} elseif ($sort == 'oldest') {
				$model = $model->orderBy(['created_at' => SORT_ASC]);
			} elseif ($sort == 'a-z') {
				$model = $model->orderBy(['title' => SORT_ASC]);
			} elseif ($sort == 'z-a') {
				$model = $model->orderBy(['title' => SORT_DESC]);
			} else {
				$model = $model->orderBy(['created_at' => SORT_DESC]);
			}
		}

        $provider = new ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => 30,
                'validatePage'=>false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount.' record found',$provider);

        //return (new ApiResponse)->success($model->all(), ApiResponse::SUCCESSFUL, 'Record found');
	}

	public function actionFeedVideo() {
		$model = new Feed;
		$model->attributes = Yii::$app->request->post();
		$model->user_id = Yii::$app->user->id;
		if (!$model->validate()) {
			return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not validated!');
		}

		if (!$model->saveVideoFeed()) {
			return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not inserted');
		}

		return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record inserted');
	}

	public function actionDiscussion() {
		$class_id = Yii::$app->request->get('class_id');
		$date = Yii::$app->request->get('date');
		$sort = Yii::$app->request->get('sort');
		$teacher_id = Yii::$app->user->id;
		$model = new \yii\base\DynamicModel(compact('class_id', 'teacher_id', 'type'));
		$model->addRule(['class_id', 'teacher_id'], 'integer')
			->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id']]);

		if (!$model->validate()) {
			return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		$model = Feed::find()->where(['user_id' => $teacher_id, 'type' => SharedConstant::FEED_TYPES[0]]);

		if ($class_id) {
			$model = $model->andWhere(['class_id' => $class_id]);

		}

		if ($date) {
			$model = $model->andWhere('created_at >= :date_value', [':date_value' => $date]);
		}

		if ($sort) {
			if ($sort == 'newest') {
				$model = $model->orderBy(['created_at' => SORT_DESC]);
			} elseif ($sort == 'oldest') {
				$model = $model->orderBy(['created_at' => SORT_ASC]);
			} elseif ($sort == 'a-z') {
				$model = $model->orderBy(['description' => SORT_ASC]);
			} elseif ($sort == 'z-a') {
				$model = $model->orderBy(['description' => SORT_DESC]);
			} else {
				$model = $model->orderBy(['created_at' => SORT_DESC]);
			}
		}

        $provider = new ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => 30,
                'validatePage'=>false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount.' record found',$provider);

		//return (new ApiResponse)->success($model->all(), ApiResponse::SUCCESSFUL, 'Record found');
	}

	public function actionVideo() {
		$class_id = Yii::$app->request->get('class_id');
		$format = Yii::$app->request->get('format');
		$date = Yii::$app->request->get('date');
		$sort = Yii::$app->request->get('sort');
		$teacher_id = Yii::$app->user->id;
		$model = new \yii\base\DynamicModel(compact('class_id', 'teacher_id', 'type'));
		$model->addRule(['class_id', 'teacher_id'], 'integer')
			->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id']]);

		if (!$model->validate()) {
			return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		$model = $this->modelClass::find()
					->andWhere(['practice_material.type' => SharedConstant::FEED_TYPES[4]]);

		if ($class_id) {
			$model = $model->innerJoin('homeworks', 'homeworks.teacher_id = practice_material.user_id')
						->andWhere(['homeworks.class_id' => $class_id]);
		}

		if ($format) {
			$model = $model->andWhere(['extension' => $format]);
		}

		if ($date) {
			$model = $model->andWhere('practice_material.created_at >= :date_value', [':date_value' => $date]);
		}

		if ($sort) {
			if ($sort == 'newest') {
				$model = $model->orderBy(['created_at' => SORT_DESC]);
			} elseif ($sort == 'oldest') {
				$model = $model->orderBy(['created_at' => SORT_ASC]);
			} elseif ($sort == 'a-z') {
				$model = $model->orderBy(['title' => SORT_ASC]);
			} elseif ($sort == 'z-a') {
				$model = $model->orderBy(['title' => SORT_DESC]);
			} else {
				$model = $model->orderBy(['created_at' => SORT_DESC]);
			}
		}

        $provider = new ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => 30,
                'validatePage'=>false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount.' record found',$provider);

        //return (new ApiResponse)->success($model->all(), ApiResponse::SUCCESSFUL, 'Record found');
	}

	public function actionAssessment() {
		$class_id = Yii::$app->request->get('class_id');
		$format = Yii::$app->request->get('format');
		$date = Yii::$app->request->get('date');
		$sort = Yii::$app->request->get('sort');
		$teacher_id = Yii::$app->user->id;
		$model = new \yii\base\DynamicModel(compact('class_id', 'teacher_id', 'type'));
		$model->addRule(['class_id', 'teacher_id'], 'integer')
			->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id']]);

		if (!$model->validate()) {
			return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		$model = Homeworks::find()->where(['teacher_id' => $teacher_id, 'type' => SharedConstant::HOMEWORK_TYPES[0]]);

		if ($class_id) {
			$model = $model->andWhere(['class_id' => $class_id]);

		}

		if ($date) {
			$model = $model->andWhere('practice_material.created_at >= :date_value', [':date_value' => $date]);
		}

		if ($sort) {
			if ($sort == 'newest') {
				$model = $model->orderBy(['created_at' => SORT_DESC]);
			} elseif ($sort == 'oldest') {
				$model = $model->orderBy(['created_at' => SORT_ASC]);
			} elseif ($sort == 'a-z') {
				$model = $model->orderBy(['title' => SORT_ASC]);
			} elseif ($sort == 'z-a') {
				$model = $model->orderBy(['title' => SORT_DESC]);
			} else {
				$model = $model->orderBy(['created_at' => SORT_DESC]);
			}
		}

        $provider = new ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => 30,
                'validatePage'=>false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount.' record found',$provider);

        //return (new ApiResponse)->success($model->all(), ApiResponse::SUCCESSFUL, 'Record found');
	}

	public function actionHomeworkSummary() {
		$id = Yii::$app->request->get('id');
		$data = Yii::$app->request->get('data');
		$model = new \yii\base\DynamicModel(compact('id', 'data'));
		$model->addRule(['id', 'data'], 'required')
			->addRule(['id'], 'integer')
			->addRule(['data'], 'string');

		if (!$model->validate()) {
			return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
		}

		if ($data == 'student') {
			$model = User::find()
						->innerJoin('quiz_summary', 'quiz_summary.student_id = user.id')
						->where(['user.type' => SharedConstant::ACCOUNT_TYPE[3]])
						->andWhere(['quiz_summary.homework_id' => $id, 'quiz_summary.submit' => SharedConstant::VALUE_ONE])
						->all();
			
		} else if ($data == 'summary') {
			$model = Homeworks::find()->where(['id' => $id])->one();
		}

		if (!$model) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
		}

		return (new ApiResponse)->success($data == 'summary' ? $model->getHomeworkSummary() : $model, ApiResponse::SUCCESSFUL, 'Record found');
	}
}
