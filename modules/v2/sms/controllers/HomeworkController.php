<?php

namespace app\modules\v2\sms\controllers;

use app\modules\v2\components\SmsAuthentication;
use app\modules\v2\components\{Utility};
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Schools;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\school\models\SmsHomework;
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

    public function beforeAction($action)
    {
        if (!SmsAuthentication::checkStatus()) {
            $this->asJson(\Yii::$app->params['customError401']);
            return false;
        }
        return parent::beforeAction($action);
    }

    public function actionClassHomework($class_id, $sort = 'created')
    {
        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id'));
        $model->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id' => 'class_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $homeworks = SmsHomework::find()
            ->where(['class_id' => $class_id, 'type' => 'homework','publish_status'=>1])
            ->andWhere(['between', 'homeworks.created_at', Yii::$app->params['first_term_start'], Yii::$app->params['third_term_end']])
            ->orderBy('id DESC');
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
}