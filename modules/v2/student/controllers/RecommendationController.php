<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\Adaptivity;
use app\modules\v2\components\CustomHttpBearerAuth;

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
        $submitted_recommendations = ArrayHelper::getColumn(
            Homeworks::find()->select('reference_id')->where(['reference_type' => SharedConstant::REFERENCE_TYPE[SharedConstant::VALUE_TWO], 'student_id' => $studentID])->all(),
            'reference_id'
        );

//        $model = Recommendations::find()
//            ->where([
//                'student_id' => $studentID,
//                'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ONE],
//            ])
//            ->andWhere(['NOT IN', 'id', $submitted_recommendations])
//            ->andWhere('DAY(CURDATE()) = DAY(created_at)')
//            ->all();

        $attempted_topic = RecommendationTopics::find()->where([
            'student_id' => $studentID,
            'object_type'=>'practice'
        ])
            ->select(['object_id'])
            ->andWhere('DAY(CURDATE()) = DAY(created_at)')
            ->asArray()
            ->all();

        $videos = RecommendationTopics::find()->where([
            'student_id' => $studentID,
            'object_type'=>'video'
        ])
            ->select(['object_id'])
            ->andWhere('DAY(CURDATE()) = DAY(created_at)')
            ->asArray()
            ->all();



        $attempted_topic = QuizSummaryDetails::find()
            ->alias('qsd')
            ->leftJoin('subject_topics st', 'st.id = qsd.topic_id')
            ->select([
                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                'qsd.topic_id as id',
                Utility::ImageQuery('st'),
                'st.topic',
                'st.subject_id',
                new Expression("'practice' as type"),
            ])
            ->where([
                'topic_id' => ArrayHelper::getColumn($attempted_topic,'object_id'),
                'student_id' => $studentID
            ])
            ->groupBy('st.id')
            ->asArray()
            ->limit(8)
            ->all();

        array_multisort(array_column($attempted_topic, 'score'), $attempted_topic);


        $video = VideoContent::find()
            ->select([
                'video_content.*',
                new Expression("'video' as type"),
                'gc.id class_id',
                'gc.description class_name',
            ])
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->innerJoin('subject_topics st', 'st.id = video_assign.topic_id')
            ->innerJoin('global_class gc', 'gc.id = st.class_id')
            ->where(['video_content.id' => ArrayHelper::getColumn($videos,'object_id')])
            ->limit(SharedConstant::VALUE_TWO)
            ->asArray()
            ->all();

        $todayRecommendation = Adaptivity::GenerateStudentSingleMixVideoRecommendations($attempted_topic, $video);

        if (!$todayRecommendation) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Daily recommendation not found');
        }

        return (new ApiResponse)->success($todayRecommendation, ApiResponse::SUCCESSFUL, 'Daily recommendation found');
    }
}