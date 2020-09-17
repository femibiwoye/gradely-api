<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\models\Classes;
use app\modules\v2\models\Feed;
use app\modules\v2\models\FeedLike;
use app\modules\v2\models\FileLog;
use app\modules\v2\models\PracticeTopics;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\VideoAssign;
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
    private $topics = array();
    private $weekly_recommended_topics = array();
    private $daily_recommended_topics = array();
    private $subjects;

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

    public function actionGenerateWeeklyRecommendation()
    {
        if (date('l') != SharedConstant::CURRENT_DAY) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'The weekly Recommendation cannot be generated on a ' . date('l'));
        }

        //student_recommendations depicts the students that has received the weekly recommendation
        $student_recommendations = ArrayHelper::getColumn(
            Recommendations::find()
                ->where([
                    'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ZERO],
                    'DATE(created_at)' => date('Y-m-d')
                ])
                ->andWhere('WEEK(CURDATE()) = WEEK(created_at)')//checking on-going week
                ->all(),
            'student_id'
        );

        //student_ids depicts the list of students
        $student_ids = ArrayHelper::getColumn(
            User::find()->where(['type' => SharedConstant::TYPE_STUDENT])->andWhere(['<>', 'status', SharedConstant::VALUE_ZERO])->andWhere(['NOT IN', 'id', $student_recommendations])->all(),
            'id'
        );


        if (empty($student_ids)) {
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Weekly recommendations are already generated');
        }


        foreach ($student_ids as $student) {
            try {
                $this->weeklyRecommendation($student);
                $this->weekly_recommended_topics = [];
                $this->subjects = [];
                $this->topics = [];
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($this->topics)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Weekly recommendations not found');
        }

        return (new ApiResponse)->success($this->topics, ApiResponse::SUCCESSFUL, 'Weekly recommendations found');
    }

    public function weeklyRecommendation($student)
    {
        $school_id = StudentSchool::find()
            ->select(['school_id', 'class_id'])
            ->where(['student_id' => $student])
            ->asArray()
            ->one();

        if (!$school_id) {
            $term = SessionTermOnly::widget(['nonSchool' => true]);
            $week = SessionTermOnly::widget(['nonSchool' => true, 'weekOnly' => true]);
            $classID = User::findOne(['id' => $student])->class;
        } else {
            $term = SessionTermOnly::widget(['id' => $school_id['school_id']]);
            $week = SessionTermOnly::widget(['id' => $school_id['school_id'], 'weekOnly' => true]);
            $classID = Classes::findOne(['id' => $school_id['class_id']])->global_class_id;
        }

        $this->subjects = ArrayHelper::getColumn(QuizSummary::find()
            ->select('subject_id')
            ->where(['student_id' => $student])
            ->groupBy('subject_id')
            ->asArray()
            ->all(),
            'subject_id'
        );

        $previous_week_recommendations = ArrayHelper::getColumn(RecommendationTopics::find()
            ->select('subject_id')
            ->where('WEEK(CURDATE()) = WEEK(created_at) - 1')
            ->groupBy('subject_id')
            ->asArray()
            ->all(),
            'subject_id'
        );

        $this->previousWeekRecommendedSubjects($previous_week_recommendations); //filters out the previous week subjects.
        foreach ($this->subjects as $subject) {
            $model = SubjectTopics::find()
                ->select([
                    'subject_topics.id',
                    'subject_topics.topic',
                    'subject_topics.week_number',
                    'subject_topics.term',
                    'subjects.name as subject_name',
                    'subjects.id as subject_id',
                    new Expression("'practice' AS type"),
                ])
                ->innerJoin('subjects', 'subjects.id = subject_topics.subject_id')
                ->where([
                    'subject_topics.subject_id' => $subject,
                    'subject_topics.term' => $term,
                    'subject_topics.class_id' => $classID
                ])
                ->orWhere(['<=', 'subject_topics.week_number', $week])
//                ->orWhere(['<', 'subject_topics.week_number', $week])
//                ->orWhere(['>', 'subject_topics.week_number', $week])
                ->asArray()
                ->limit(SharedConstant::VALUE_ONE)
                ->all();

            if (sizeof($this->weekly_recommended_topics) == SharedConstant::VALUE_THREE) {
                break;
            }

            $this->weekly_recommended_topics = array_merge($this->weekly_recommended_topics, $model);
        }

        if (!$this->weekly_recommended_topics) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Weekly recommendations not found');
        }

        $weekly_recommended_videos = VideoContent::find()
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->where([
                'video_assign.topic_id' => ArrayHelper::getColumn(
                    $this->weekly_recommended_topics, 'id')
            ])
            ->limit(SharedConstant::VALUE_TWO)
            ->all();

        if (count($this->weekly_recommended_topics) < SharedConstant::VALUE_THREE) {
            $this->randomWeeklyRecommendationTopics($this->weekly_recommended_topics, $this->subjects, $previous_week_recommendations, 'weekly', $term, $week, $classID);
        }

        $this->topics = array_merge($this->weekly_recommended_topics, $weekly_recommended_videos);
        $this->createRecommendations($this->topics, $student, SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ZERO]);
    }

    private function previousWeekRecommendedSubjects($previous_week_recommendations)
    {
        if (!empty($previous_week_recommendations)) {
            $this->subjects = array_diff($this->subjects, $previous_week_recommendations);
            if (count($this->subjects) == SharedConstant::VALUE_ONE) {
                $keys = array_rand($previous_week_recommendations, SharedConstant::VALUE_TWO); //select random keys from the previous week recommendations
                $this->subjects = array_merge($this->subjects, $previous_week_recommendations[$keys[SharedConstant::VALUE_ZERO]], $previous_week_recommendations[$keys[SharedConstant::VALUE_ONE]]);
            } elseif (empty($this->subjects)) {
                $keys = array_rand($previous_week_recommendations, SharedConstant::VALUE_THREE); //select random keys from the previous week recommendations
                $this->subjects = array_merge($this->subjects, $previous_week_recommendations[$keys[SharedConstant::VALUE_ZERO]], $previous_week_recommendations[$keys[SharedConstant::VALUE_ONE]], $previous_week_recommendations[$keys[SharedConstant::VALUE_TWO]]);
            } elseif (count($this->subjects) == SharedConstant::VALUE_TWO) {
                $keys = array_rand($previous_week_recommendations, SharedConstant::VALUE_ONE); //select random keys from the previous week recommendations
                $this->subjects = array_merge($this->subjects, $previous_week_recommendations[$keys[SharedConstant::VALUE_ZERO]]);
            }
        }
    }

    private function createRecommendations($recommendations, $student, $recommendation_type)
    {
        if (!empty($recommendations)) {
            $dbtransaction = Yii::$app->db->beginTransaction();
            try {
                $model = new Recommendations;
                $model->student_id = $student;
                $model->category = $recommendation_type;
                if (!$model->save()) {
                    return false;
                }

                if (!$this->createRecommendedTopics($recommendations, $model)) {
                    return false;
                }

                $dbtransaction->commit();
            } catch (Exception $e) {
                $dbtransaction->rollBack();
                return false;
            }

            return true;
        }
    }

    private function createRecommendedTopics($objects, $recommendation)
    {
        foreach ($objects as $object) {
            $model = new RecommendationTopics;
            $model->recommendation_id = $recommendation->id;
            $model->subject_id = $object['subject_id'];
            $model->student_id = $recommendation->student_id;
            $model->object_id = $object['id'];
            $model->object_type = isset($object['type']) ? $object['type'] : 'video';
            if (!$model->save(false)) {
                return false;
            }
        }

        return true;
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

    public function actionGenerateDailyRecommendations()
    {
        //student_recommendations depicts the students that has received the daily recommendation
        $student_recommendations = ArrayHelper::getColumn(
            Recommendations::find()
                ->where([
                    'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ONE],
                    'DATE(created_at)' => date('Y-m-d')
                ])
                ->andWhere('DAY(CURDATE()) = DAY(created_at)')//checking on-going day
                ->all(),
            'student_id'
        );

        //student_ids depicts the list of students
        $student_ids = ArrayHelper::getColumn(
            User::find()->where(['type' => SharedConstant::TYPE_STUDENT])->andWhere(['<>', 'status', SharedConstant::VALUE_ZERO])->andWhere(['NOT IN', 'id', $student_recommendations])->all(),
            'id'
        );


        if (empty($student_ids)) {
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Daily recommendations are already generated');
        }


        foreach ($student_ids as $student) {
            try {
                $this->dailyRecommendation($student);
                $this->daily_recommended_topics = [];
                $this->subjects = [];
                $this->topics = [];
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($this->topics)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Daily recommendations not found');
        }

        return (new ApiResponse)->success($this->topics, ApiResponse::SUCCESSFUL, 'Daily recommendations found');
    }

    private function dailyRecommendation($student)
    {
        $school_id = StudentSchool::find()
            ->select(['school_id', 'class_id'])
            ->where(['student_id' => $student])
            ->asArray()
            ->one();

        if (!$school_id) {
            $classID = User::findOne(['id' => $student])->class;
        } else {
            $classID = Classes::findOne(['id' => $school_id['class_id']])->global_class_id;
        }

        //weekly_recommended_topics is to store the topics that are already exist in weekly_recommendation
        $weekly_recommended_topics = ArrayHelper::getColumn(
            RecommendationTopics::find()
                ->innerJoin('recommendations', 'recommendations.id = recommendation_topics.recommendation_id')
                ->where('WEEKDAY(recommendations.created_at) = ' . SharedConstant::VALUE_SIX)
                ->andWhere(['student_id' => $student])
                ->all(),
            'object_id'
        );

        $subjects = ArrayHelper::getColumn(QuizSummary::find()
            ->select('subject_id')
            ->where(['student_id' => $student])
            ->groupBy('subject_id')
            ->asArray()
            ->all(),
            'subject_id'
        );

        foreach ($subjects as $subject) {
            $model = SubjectTopics::find()
                ->select([
                    'subject_topics.id',
                    'subject_topics.topic',
                    'subject_topics.week_number',
                    'subject_topics.term',
                    'subjects.name as subject_name',
                    'subjects.id as subject_id',
                    new Expression("'practice' AS type"),
                ])
                ->innerJoin('subjects', 'subjects.id = subject_topics.subject_id')
                ->where([
                    'subject_topics.subject_id' => $subject,
                    'subject_topics.class_id' => $classID
                ])
                ->andWhere(['NOT IN', 'subject_topics.id', $weekly_recommended_topics])
                ->asArray()
                ->limit(SharedConstant::VALUE_ONE)
                ->all();

            if (sizeof($this->daily_recommended_topics) == SharedConstant::VALUE_THREE) {
                break;
            }

            $this->daily_recommended_topics = array_merge($this->daily_recommended_topics, $model);
        }

        if (!$this->daily_recommended_topics) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Recommendations not found');
        }

        $daily_recommended_videos = VideoContent::find()
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->where([
                'video_assign.topic_id' => ArrayHelper::getColumn(
                    $this->daily_recommended_topics, 'id')
            ])
            ->limit(SharedConstant::VALUE_TWO)
            ->all();

        if (count($this->daily_recommended_topics) < SharedConstant::VALUE_THREE) {
            $this->randomDailyRecommendationTopics($this->daily_recommended_topics, $subjects, $weekly_recommended_topics, 'daily');
        }

        $this->topics = array_merge($this->daily_recommended_topics, $daily_recommended_videos);
        $this->createRecommendations($this->topics, $student, SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ONE]);
    }

    private function randomDailyRecommendationTopics($recommended_topics, $subjects, $weekly_recommended_topics)
    {
        $recommended_subjects = ArrayHelper::getColumn($recommended_topics, 'subject_id');
        if (count($recommended_topics) == SharedConstant::VALUE_TWO) {
            foreach ($subjects as $subject) {
                $this->selectTopic($subject['id'], $weekly_recommended_topics, $recommended_subjects, 'daily');
                if (count($this->daily_recommended_topics) == SharedConstant::VALUE_THREE) {
                    break;
                }
            }
        } elseif (count($this->daily_recommended_topics) == SharedConstant::VALUE_ONE) {
            foreach ($subjects as $subject) {
                $this->selectTopic($subject['id'], $weekly_recommended_topics, $recommended_subjects, 'daily');
                if (count($this->daily_recommended_topics) == SharedConstant::VALUE_THREE) {
                    break;
                }
            }
        } else {
            foreach ($subjects as $subject) {
                $this->selectTopic($subject['id'], $weekly_recommended_topics, $recommended_subjects, 'daily');
                if (count($this->daily_recommended_topics == SharedConstant::VALUE_THREE)) {
                    break;
                }
            }
        }
    }

    private function randomWeeklyRecommendationTopics($recommended_topics, $subjects, $weekly_recommended_topics, $term, $week, $classID)
    {
        $recommended_subjects = ArrayHelper::getColumn($recommended_topics, 'subject_id');
        if (count($recommended_topics) == SharedConstant::VALUE_TWO) {
            foreach ($subjects as $subject) {
                $this->selectTopic($subject, $weekly_recommended_topics, $recommended_subjects, 'weekly', $term, $week, $classID);
                if (count($this->weekly_recommended_topics) == SharedConstant::VALUE_THREE) {
                    break;
                }
            }
        } elseif (count($this->daily_recommended_topics) == SharedConstant::VALUE_ONE) {
            foreach ($subjects as $subject) {
                $this->selectTopic($subject, $weekly_recommended_topics, $recommended_subjects, 'weekly', $term, $week, $classID);
                if (count($this->weekly_recommended_topics) == SharedConstant::VALUE_THREE) {
                    break;
                }
            }
        } else {
            foreach ($subjects as $subject) {
                $this->selectTopic($subject, $weekly_recommended_topics, $recommended_subjects, 'weekly', $term, $week, $classID);
                if (count($this->weekly_recommended_topics == SharedConstant::VALUE_THREE)) {
                    break;
                }
            }
        }
    }

    private function selectTopic($subject, $weekly_recommended_topics, $recommended_subjects, $type, $term = null, $week = null, $classID = null)
    {
        $model = SubjectTopics::find()
            ->select([
                'subject_topics.id',
                'subject_topics.topic',
                'subject_topics.week_number',
                'subject_topics.term',
                'subjects.name as subject_name',
                'subjects.id as subject_id',
                new Expression("'practice' AS type"),
            ])
            ->innerJoin('subjects', 'subjects.id = subject_topics.subject_id')
            ->where([
                'subject_topics.subject_id' => $subject,
                //'subject_topics.class_id' => $school_id['class_id']
            ])
            ->andWhere(['NOT IN', 'subject_topics.id', $weekly_recommended_topics])
            ->andWhere(['NOT IN', 'subject_topics.id', $recommended_subjects])
            ->asArray();

        if ($type == 'weekly') {
            $model = $model->where([
                //'subject_topics.subject_id' => $subjects,
                'subject_topics.term' => $term,
                'subject_topics.class_id' => $classID
            ])
                ->orWhere(['>', 'subject_topics.week_number', $week]);
        }

        $model = $model->limit(SharedConstant::VALUE_ONE)
            ->all();

        if ($type == 'weekly') {
            $this->weekly_recommended_topics = array_merge($this->weekly_recommended_topics, $model);
        } else {
            $this->daily_recommended_topics = array_merge($this->daily_recommended_topics, $model);
        }
    }

    public function actionDailyRecommendation()
    {
        $submitted_recommendations = ArrayHelper::getColumn(
            Homeworks::find()->select('reference_id')->where(['reference_type' => SharedConstant::REFERENCE_TYPE[SharedConstant::VALUE_TWO]]),
            'reference_id'
        );

        $model = Recommendations::find()
            ->where([
                'student_id' => Yii::$app->user->id,
                'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ONE],
            ])
            ->andWhere(['NOT IN', 'id', $submitted_recommendations])
            ->andWhere('DAY(CURDATE()) = DAY(created_at)')
            ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Daily recommendation not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Daily recommendation found');
    }
}