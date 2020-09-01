<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\components\Utility;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\models\QuizSummaryDetails;
use app\modules\v2\models\Remarks;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\{Homeworks, ApiResponse};

use app\modules\v2\student\models\HomeworkReport;
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

    public function actionCompletedHomework($child = null)
    {
        $student_id = Utility::getParentChildID($child);

        $models = StudentHomeworkReport::find()
            ->innerJoin('quiz_summary', 'quiz_summary.homework_id = homeworks.id')
            ->where(['quiz_summary.student_id' => $student_id, 'homeworks.type' => 'homework', 'quiz_summary.submit' => SharedConstant::VALUE_ONE]);

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

    public function actionNewHomework($child = null)
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

    public function actionHomeworkScore($homework_id)
    {
        $student_id = Yii::$app->user->id;
        $homework = QuizSummary::find()->where(['student_id' => $student_id, 'homework_id' => $homework_id])->one();
        if (!$homework) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not found!');
        }
        if ($homework->submit != 1) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not scored');
        }

        return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homework scored successfully');
    }


    public function actionHomeworkReport($id)
    {
        $student_id = Utility::getParentChildID();
        $model = HomeworkReport::findOne(['student_id' => $student_id, 'homework_id' => $id, 'submit' => 1]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework report not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Student report found');
    }

    public function actionHomeworkReviewQuestion($homework_id)
    {
        $summary_details = QuizSummaryDetails::find()->alias('qsd')
            ->innerJoin('quiz_summary', 'quiz_summary.id = qsd.quiz_id')
            ->innerJoin('homeworks', 'homeworks.id = quiz_summary.homework_id')
            ->innerJoin('questions', 'questions.id = qsd.question_id')
            ->andWhere([
                'quiz_summary.homework_id' => $homework_id,
                'homeworks.publish_status' => SharedConstant::VALUE_ONE,
                'homeworks.type' => 'homework',

            ])
            ->all();

        if (!$summary_details) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not found!');
        }

        return (new ApiResponse)->success($summary_details, ApiResponse::SUCCESSFUL, 'Questions succcessfully retrieved');
    }

    public function actionHomeworkReviewRecommendation($homework_id)
    {
        $topics = SubjectTopics::find()->alias('topic')
            ->innerJoin('quiz_summary_details qsd', 'topic.id = qsd.topic_id')
            ->andWhere(['qsd.homework_id' => $homework_id])
            ->all();

        if (!$topics) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No Recommendation found!');
        }

        return (new ApiResponse)->success($topics, ApiResponse::SUCCESSFUL, 'Homework recommendation retrieved');

    }
}