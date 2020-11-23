<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\{Utility};
use app\modules\v2\models\{Homeworks, TeacherClass, ApiResponse};
use app\modules\v2\models\Schools;
use Yii;
use yii\data\ActiveDataProvider;
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

    public function actionClassHomeworks($class_id, $sort = 'created')
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id'));
        $model->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id' => 'class_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $homeworks = $this->modelClass::find()
            ->where(['class_id' => $class_id])->orderBy('id DESC');
        if (!$homeworks->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homeworks not found');
        }
        if ($sort == 'created') {
            $homeworks = $homeworks->orderBy('created_at DESC');
        } elseif ($sort == 'open') {
            $homeworks = $homeworks->orderBy('open_date DESC');
        } elseif ($sort == 'close') {
            $homeworks = $homeworks->orderBy('close_date DESC');
        }

        $provider = new ActiveDataProvider([
            'query' => $homeworks,
            'pagination' => [
                'pageSize' => 20,
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['open_date', 'close_date'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' homework found', $provider);
    }

    public function actionHomeworkReview($homework_id)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('homework_id', 'school_id'));
        $model->addRule(['homework_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $homework = Homeworks::find()->where(['id' => $homework_id])->one();
        if (!$homework) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homeworks not found');
        }

        return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homeworks record found');
    }

    public function actionHomeworkPerformance($homework_id)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('homework_id', 'school_id'));
        $model->addRule(['homework_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $homework = Homeworks::find()->where(['id' => $homework_id])->one();
        if (!$homework) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homeworks not found');
        }

        return (new ApiResponse)->success($homework->homeworkPerformance, ApiResponse::SUCCESSFUL, 'Homework Performance record found');
    }
}