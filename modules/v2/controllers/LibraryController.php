<?php

namespace app\modules\v2\controllers;

use app\modules\v2\models\PracticeMaterial;
use app\modules\v2\models\UserModel;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\models\{TeacherClass, ApiResponse, Feed, Homeworks, User, Questions, PracticeTopics, Classes};
use app\modules\v2\teacher\models\{HomeworkSummary, ClassReport};
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

    public function actionIndex()
    {
        $class_id = Yii::$app->request->get('class_id');
        $format = Yii::$app->request->get('format');
        $date = Yii::$app->request->get('date');
        $sort = Yii::$app->request->get('sort');

        if (Yii::$app->user->identity->type == 'teacher') {
            $teacher_id = Yii::$app->user->id;
            $model = new \yii\base\DynamicModel(compact('class_id', 'teacher_id'));
            $model->addRule(['class_id', 'teacher_id'], 'integer')
                ->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id']]);
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
            }
        }


        $model = $this->modelClass::find()
            ->innerJoin('homeworks', 'homeworks.teacher_id = practice_material.user_id')
            ->andWhere(['practice_material.filetype' => 'document']);

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
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' record found', $provider);

        //return (new ApiResponse)->success($model->all(), ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionUploadVideo()
    {

        $class_id = Yii::$app->request->post('class_id');
        $teacher_id = Yii::$app->user->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'teacher_id'));
        $model->addRule(['class_id'], 'integer')
            ->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id']]);
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = new PracticeMaterial();
        $model->attributes = Yii::$app->request->post();
        $model->user_id = $teacher_id;
        $model->filetype = 'video';
        $model->type = 'feed';
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Upload not validated!');
        }

        if (!$model->saveVideoFeed($class_id)) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video not uploaded');
        }


        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Video uploaded');
    }

    public function actionDiscussion()
    {
        $class_id = Yii::$app->request->get('class_id');
        $date = Yii::$app->request->get('date');
        $sort = Yii::$app->request->get('sort');
        $teacher_id = Yii::$app->user->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'teacher_id', 'type'));
        $model
            ->addRule(['class_id'], 'required')
            ->addRule(['class_id', 'teacher_id'], 'integer')
            ->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = Feed::find()->where(['type' => SharedConstant::FEED_TYPES[0], 'view_by' => ['class', 'all']]);

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
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' record found', $provider);

        //return (new ApiResponse)->success($model->all(), ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionVideo()
    {
        $class_id = Yii::$app->request->get('class_id');
        $format = Yii::$app->request->get('format');
        $date = Yii::$app->request->get('date');
        $sort = Yii::$app->request->get('sort');
        $teacher_id = Yii::$app->user->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'teacher_id', 'type'));
        $model
            ->addRule(['class_id'], 'required')
            ->addRule(['class_id', 'teacher_id'], 'integer')
            ->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = $this->modelClass::find()
            ->andWhere(['practice_material.filetype' => SharedConstant::FEED_TYPES[4]]);

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
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' record found', $provider);

        //return (new ApiResponse)->success($model->all(), ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionAssessment()
    {
        $class_id = Yii::$app->request->get('class_id');
        //$format = Yii::$app->request->get('format');
        $date = Yii::$app->request->get('date');
        $sort = Yii::$app->request->get('sort');
        $teacher_id = Yii::$app->user->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'teacher_id', 'type'));
        $model
            ->addRule(['class_id'], 'required')
            ->addRule(['class_id', 'teacher_id'], 'integer')
            ->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = Homeworks::find()->where(['teacher_id' => $teacher_id, 'type' => SharedConstant::HOMEWORK_TYPES[0]]);

        if ($class_id) {
            $model = $model->andWhere(['class_id' => $class_id]);

        }

        if ($date) {
            $model = $model->andWhere('homeworks.created_at >= :date_value', [':date_value' => $date]);
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
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' record found', $provider);

        //return (new ApiResponse)->success($model->all(), ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionClassReport()
    {
        $id = Yii::$app->request->get('class_id');
        $data = Yii::$app->request->get('data');
        $subject = Yii::$app->request->get('subject');
        $term = Yii::$app->request->get('term');
        $date = Yii::$app->request->get('date');
        $model = new \yii\base\DynamicModel(compact('id', 'data', 'subject', 'term', 'date'));
        $model->addRule(['id', 'data'], 'required')
            ->addRule(['id'], 'integer')
            ->addRule(['data', 'term', 'date', 'subject'], 'string');

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = (new ClassReport)->getReport();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }
}
