<?php

namespace app\modules\v2\parent\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\models\HomeworkReport;
use app\modules\v2\models\Parents;
use app\modules\v2\models\QuizSummaryDetails;
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

        $parent_id = Yii::$app->user->id;

        $parent = Parents::findOne(['user_id' => $parent_id]);

        $models = StudentHomeworkReport::find()
            ->innerJoin('quiz_summary', 'quiz_summary.homework_id = homeworks.id')
            ->where(['quiz_summary.student_id' => $parent->student_id, 'homeworks.type' => 'homework', 'quiz_summary.submit' => SharedConstant::VALUE_ONE])
        ->orderBy('id DESC');

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

        $parent_id = Yii::$app->user->id;

        $parent = Parents::findOne(['user_id' => $parent_id]);

        $models = $this->modelClass::find()
            ->innerJoin('student_school', 'student_school.class_id = homeworks.class_id')
            ->where(['homeworks.type' => 'homework', 'homeworks.status' => SharedConstant::VALUE_ONE, 'homeworks.publish_status' => SharedConstant::VALUE_ONE])
            ->andWhere(['<', 'UNIX_TIMESTAMP(open_date)', time()])
            ->andWhere(['>', 'UNIX_TIMESTAMP(close_date)', time()])
            ->andWhere(['homeworks.student_id' => $parent->student_id]);

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

    $parent_id = Yii::$app->user->id;

    $parent = Parents::findOne(['user_id' => $parent_id]);

    $homework = HomeworkReport::find()
        ->innerJoin('quiz_summary summary', 'summary.homework_id = homeworks.id')
        ->andWhere([
            'homeworks.id' => $homework_id,
            'homeworks.student_id' => $parent->student_id,
            'homeworks.publish_status' => 1,
        ])
        ->one();

    if(!$homework){
        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not found!');
    }

    return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homework Score succcessfully retrieved');
}

    public function actionHomeworkReviewQuestion($homework_id){

        $parent_id = Yii::$app->user->id;

        $parent = Parents::findOne(['user_id' => $parent_id]);

        $summary_details = QuizSummaryDetails::find()->alias('qsd')
            ->innerJoin('quiz_summary', 'quiz_summary.id = qsd.quiz_id')
            ->innerJoin('homeworks', 'homeworks.id = quiz_summary.homework_id')
            ->innerJoin('questions', 'questions.id = qsd.question_id')
            ->andWhere([
                'quiz_summary.homework_id' => $homework_id,
                'homeworks.publish_status' => 1,
                'homeworks.type' => 'homework',
                'qsd.student_id' => $parent->student_id,
            ])
            ->all();

        if(!$summary_details){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not found!');
        }

        return (new ApiResponse)->success($summary_details, ApiResponse::SUCCESSFUL, 'Questions succcessfully retrieved');
    }
}