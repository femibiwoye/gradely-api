<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\models\HomeworkReport;
use app\modules\v2\models\Parents;
use app\modules\v2\models\{Homeworks, ApiResponse};

use app\modules\v2\student\models\StudentHomeworkReport;
use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use app\modules\v2\components\SharedConstant;


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

    public function actionCompletedHomework()
    {
        $models = StudentHomeworkReport::find()
            ->innerJoin('quiz_summary', 'quiz_summary.homework_id = homeworks.id')
            ->where(['quiz_summary.student_id' => Yii::$app->user->id, 'homeworks.type' => 'homework', 'quiz_summary.submit' => SharedConstant::VALUE_ONE]);

        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $models,
            'pagination' => [
                'pageSize' => 10,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionNewHomework()
    {
        $models = $this->modelClass::find()
            ->innerJoin('student_school', 'student_school.class_id = homeworks.class_id')
            ->where(['homeworks.type' => 'homework', 'homeworks.status' => SharedConstant::VALUE_ONE, 'homeworks.publish_status' => SharedConstant::VALUE_ONE])
            ->andWhere(['<', 'UNIX_TIMESTAMP(open_date)', time()])
            ->andWhere(['>', 'UNIX_TIMESTAMP(close_date)', time()]);

        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $models,
            'pagination' => [
                'pageSize' => 10,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionHomeworkScore($homework_id){

        $student_id = Yii::$app->user->id;

        $homework = HomeworkReport::find()
                    ->innerJoin('quiz_summary summary', 'summary.homework_id = homeworks.id')
                    ->andWhere([
                        'homeworks.id' => $homework_id,
                        'homeworks.student_id' => $student_id,
                        'homeworks.publish_status' => 1,
                    ])
                    ->one();

        if(!$homework){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not found!');
        }

        return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homework Score succcessfully retrieved');
    }
}