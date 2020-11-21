<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\Adaptivity;
use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\components\Recommendation;
use app\modules\v2\models\VideoContent;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use app\modules\v2\models\{Homeworks,
    ApiResponse,
    FeedComment,
    PracticeMaterial,
    Catchup,
    SubjectTopics,
    QuizSummary,
    QuizSummaryDetails,
    Subjects,
    Recommendations,
    RecommendationTopics,
    User
};
use app\modules\v2\student\models\{StartPracticeForm, StartQuizSummaryForm};
use app\modules\v2\components\{SharedConstant, Utility, SessionTermOnly, SessionWeek};


/**
 * Schools/Parent controller
 */
class RecommendationController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Catchup';

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


    public function actionHomeworkRecommendation($quiz_id)
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $quizSummary = QuizSummary::find()->where([
            'id' => $quiz_id, 'submit' => 1,
            'student_id' => Yii::$app->user->id
        ])->one();
        if (!$quizSummary)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not found');


        //$topics retrieves low scoring topic_ids
        $topics = QuizSummaryDetails::find()
            ->alias('qsd')
            ->select([
                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                'qsd.topic_id',
            ])
            ->where([
                'qsd.student_id' => Yii::$app->user->id,
                'homework_id' => $quizSummary->homework_id
            ])
            ->orderBy(['score' => SORT_ASC])
            ->asArray()
            ->limit(SharedConstant::VALUE_TWO)
            ->groupBy('qsd.topic_id')
            ->all();

        //$topic_objects retrieves topic objects
        $topic_objects = SubjectTopics::find()
            ->select([
                'subject_topics.*',
                new Expression("'practice' as type")
            ])
            ->where(['id' => ArrayHelper::getColumn($topics, 'topic_id')])
            ->asArray()
            ->all();

        //retrieves assign videos to the topic
        $video = VideoContent::find()
            ->select([
                'video_content.*',
                new Expression("'video' as type")
            ])
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->where(['video_assign.topic_id' => ArrayHelper::getColumn($topics, 'topic_id')])
            ->limit(SharedConstant::VALUE_ONE)
            ->asArray()
            ->all();

        if (!$topic_objects) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework recommendations not found');
        }


        $topics = array_merge($topic_objects, $video);

        return (new ApiResponse)->success($topics, ApiResponse::SUCCESSFUL, 'Homework recommendations found');

    }


    public function actionWeeklyRecommendations()
    {
        $recommendations = Recommendations::find()
            ->where([
                'student_id' => Yii::$app->user->id,
                'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ZERO],
            ])
            ->andWhere(['=', new Expression('DAYOFWEEK(created_at)'), SharedConstant::VALUE_ONE])
            ->all();

        if (!$recommendations) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Weekly recommendations not found');
        }

        return (new ApiResponse)->success($recommendations, ApiResponse::SUCCESSFUL, 'Weekly recommendations found');
    }

    public function actionDailyRecommendation($child = null)
    {
        $studentID = Utility::getParentChildID();
        $models = $this->FetchRecommendation($studentID, date("Y-m-d"));

        $recommendation = [];
        $isToday = false;
        foreach ($models as $model) {
            $recommendation[] = array_merge([
                'id' => $model->id,
                'is_done' => $model->is_taken,
            ], $model->raw);
            if ($model->is_taken == 0) {
                $isToday = true;
            }
        }

        if (!$isToday) {
            $models = $this->FetchRecommendation($studentID, date("Y-m-d", strtotime("+1 day")));
            $recommendation = [];
            foreach ($models as $model) {
                $recommendation[] = array_merge([
                    'id' => $model->id,
                    'is_done' => $model->is_taken,
                ], $model->raw);
            }
        }

        if (!$recommendation) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Daily recommendation not found');
        }

        return (new ApiResponse)->success($recommendation, ApiResponse::SUCCESSFUL, 'Daily recommendation found');

    }

    private function FetchRecommendation($studentID, $date)
    {
        return Recommendations::find()
            ->where([
                'student_id' => $studentID,
                'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ONE],
                'DATE(created_at)' => $date
            ])
            ->andWhere(['is not', 'raw', null])
            //->andWhere('DAY(CURDATE()) = DAY(created_at)')
            ->limit(6)
            ->all();

    }

    /**
     * This allow student to generate new sets of recommendations
     * @return ApiResponse
     */
    public function actionGenerateNewRecommendation()
    {
        $studentID = Utility::getParentChildID();
        $recommendation = new Recommendation();
        return (new ApiResponse)->success($recommendation->dailyRecommendation($studentID), ApiResponse::SUCCESSFUL);
    }
}