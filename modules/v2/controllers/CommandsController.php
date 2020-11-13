<?php

namespace app\modules\v2\controllers;


use app\modules\v2\components\Adaptivity;
use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\GenerateString;
use app\modules\v2\models\PracticeMaterial;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\models\QuizSummaryDetails;
use app\modules\v2\models\Recommendations;
use app\modules\v2\models\RecommendationTopics;
use app\modules\v2\models\SchoolCalendar;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\TutorSession;
use app\modules\v2\models\User;
use app\modules\v2\models\VideoAssign;
use app\modules\v2\models\VideoContent;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;


/**
 * Auth controller
 */
class CommandsController extends Controller
{
    private $topics = array();
    private $weekly_recommended_topics = array();
    private $daily_recommended_topics = array();
    private $subjects;

    /**
     * Update school calendar
     * @return int
     */
    public function actionUpdateSchoolCalendar()
    {
        return SchoolCalendar::updateAll([
            'first_term_start' => Yii::$app->params['first_term_start'],
            'first_term_end' => Yii::$app->params['first_term_end'],
            'second_term_start' => Yii::$app->params['second_term_start'],
            'second_term_end' => Yii::$app->params['second_term_end'],
            'third_term_start' => Yii::$app->params['third_term_start'],
            'third_term_end' => Yii::$app->params['third_term_end'],
            'year' => date('Y'),
            'session_name' => date('Y') + 1
        ], ['status' => 1]);
    }

    /**
     * For videos that does not have token generated. It will generate unique token to the content.
     * @return bool
     * @throws \Exception
     */
    public function actionUpdateVideoToken()
    {
        $videos = VideoContent::find()->where(['token' => null])->all();

        foreach ($videos as $video) {
            $token = GenerateString::widget(['length' => 20]);
            if (VideoContent::find()->where(['token' => $token])->exists()) {
                $video->token = GenerateString::widget(['length' => 20]);
            }
            $video->token = $token;
            $video->save();
        }

        return true;
    }

    /**
     * For media files that does not have token generated. It will generate unique token to the files.
     * @return bool
     * @throws \Exception
     */
    public function actionUpdateFileToken()
    {
        $files = PracticeMaterial::find()->where(['token' => null])->all();

        foreach ($files as $file) {
            $token = GenerateString::widget(['length' => 50]);
            if (PracticeMaterial::find()->where(['token' => $token])->exists()) {
                $file->token = GenerateString::widget(['length' => 50]);
            }
            $file->token = $token;
            $file->save();
        }

        return true;
    }


    /**
     * Weekly Recommendation
     * @return ApiResponse
     */
    public function actionGenerateWeeklyRecommendation()
    {
        if (date('l') != SharedConstant::WEEKLY_GENERATE_DAY) {
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
                $this->weekly_recommended_topics = [];
                $this->subjects = [];
                $this->topics = [];
                $this->weeklyRecommendation($student);
            } catch (\Exception $e) {
                continue;
            }
        }

//        if (empty($this->topics)) {
//            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Weekly recommendations not found');
//        }

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
                    'subject_topics.class_id',
                    'subjects.name as subject_name',
                    'subjects.id as subject_id',
                    new Expression("'practice' AS type"),
                ])
                ->innerJoin('subjects', 'subjects.id = subject_topics.subject_id')
                ->where([
                    'subject_topics.subject_id' => $subject,
                    'subject_topics.term' => strtolower($term),
                    'subject_topics.class_id' => $classID
                ])
                ->andWhere(['<=', 'subject_topics.week_number', $week])
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
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No recommendation for this child');
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
            $this->randomWeeklyRecommendationTopics($this->weekly_recommended_topics, $this->subjects, $previous_week_recommendations, $term, $week, $classID);
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

    private function createRecommendations($recommendations, $student, $category = 'daily')
    {
        if (!empty($recommendations)) {
            $dbtransaction = Yii::$app->db->beginTransaction();
            try {
                $model = new Recommendations;
                $model->student_id = $student;
                $model->category = $category;
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
            if ($object['type'] == 'video') {
                $video = VideoAssign::findOne(['content_id' => $object['id']]);
            }
            $model = new RecommendationTopics;
            $model->recommendation_id = $recommendation->id;
            $model->subject_id = $object['type'] == 'video' ? $video->topic->subject_id : $object['subject_id'];
            $model->student_id = $recommendation->student_id;
            $model->object_id = $object['id'];
            $model->object_type = $object['type'];
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }


    /**
     * Generate daily recommendation
     * @return ApiResponse
     */
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
            // try {
            $this->daily_recommended_topics = [];
            $this->subjects = [];
            $this->topics = [];
            return $this->dailyRecommendation($student);
//            } catch (\Exception $e) {
//                continue;
//            }
        }

//        if (empty($this->topics)) {
//            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Daily recommendations not found');
//        }

        return (new ApiResponse)->success($this->topics, ApiResponse::SUCCESSFUL, 'Daily recommendations found');
    }

    private function dailyRecommendation($student)
    {
        $student = 32;
        $school_id = StudentSchool::find()
            ->select(['school_id', 'class_id', 'student_id'])
            ->where(['student_id' => $student])
            ->asArray()
            ->one();

        if (!$school_id) {
            $classID = User::findOne(['id' => $student])->class;
        } else {
            $classID = Classes::findOne(['id' => $school_id['class_id']])->global_class_id;
        }

        $topics = QuizSummaryDetails::find()
            ->alias('s')
            ->select(['s.topic_id'])
            ->where(['s.student_id' => $student])
            ->innerJoin('quiz_summary q', 'q.id = s.quiz_id AND q.submit = 1')
            ->innerJoin('subject_topics st', 'st.id = s.topic_id AND st.class_id = ' . $classID)
            ->groupBy('s.topic_id')
            ->asArray()
            ->all();


        return $this->PracticeVideoRecommendation($topics, $student);


    }

    public function PracticeVideoRecommendation($topic_id, $student)
    {

//        $topic_objects = SubjectTopics::find()
//            ->leftJoin('practice_topics pt', 'pt.topic_id = subject_topics.id')
//            ->leftJoin('homeworks h', 'h.id = pt.practice_id')
//            ->select([
//                'subject_topics.*',
//                Utility::ImageQuery('subject_topics'),
//                new Expression("'practice' as type"),
//                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
//            ])
//            ->where(['subject_topics.id' => $topic_id])
//            ->asArray()
//            ->all();


        $currentWeekTerm = Utility::getStudentTermWeek(null, $student);
        $currentSubject = SubjectTopics::find()->select('id')
            ->where(['AND', ['term' => $currentWeekTerm['term']], ['>=', 'week_number', $currentWeekTerm['week']]])->asArray()->one();

        $topic_id = ArrayHelper::getColumn($topic_id, 'topic_id');
        $topic_id = isset($currentSubject['id']) ? array_merge($topic_id, [$currentSubject['id']]) : $topic_id;


        $totalTopicsCount = 8;
        $notDonePractice = RecommendationTopics::find()->where(['object_id' => $topic_id, 'object_type' => 'practice', 'is_done' => 0])
            ->limit(8)
            ->groupBy('object_id')
            ->count();
        $limit = $totalTopicsCount - $notDonePractice;

        //Method 2
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
                'topic_id' => $topic_id,
                'student_id' => $student
            ])
            ->groupBy('st.id')
            ->asArray()
            ->limit($limit)
            ->all();

        array_multisort(array_column($attempted_topic, 'score'), $attempted_topic);

        $topicsOnly = ArrayHelper::getColumn($attempted_topic, 'id');
        $videoIDs = implode(', ', ($topicsOnly));
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
            ->where(['video_assign.topic_id' => $topic_id])
            ->orderBy([new \yii\db\Expression("FIELD (video_assign.topic_id, $videoIDs)")])
            ->limit(SharedConstant::VALUE_TWO)
            ->asArray()
            ->all();


        //return $todayRecommendation = Adaptivity::GenerateStudentSingleMixVideoRecommendations($attempted_topic, $video);
        $todayRecommendation = array_merge($attempted_topic, $video);

        return $this->createRecommendations($todayRecommendation, $student, SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ONE]);

        //return $todayRecommendation;
    }


//    public function GenerateStudentSingleMixVideoPractices($topicModels)
//    {
//        $topicOrders = [];
//        foreach ($topicModels as $key => $inner) {
//            if ($key == 0) {
//                $topicOrders[] = Utility::SingleMixTopic('single', $inner);
//            }
//
//            if ($key >= 1 && $key <= 4) {
//                if (isset($topicModels[1])) {
//                    $temp = array_splice($topicModels, 1, 4);
//                    if (count($temp) == 1)
//                        $topicOrders[] = Utility::SingleMixTopic('single', $inner);
//                    else
//                        $topicOrders[] = Utility::SingleMixTopic('mix', $temp);
//                }
//            }
//
//            if ($key > 4 && $key <= 7) {
//                if (isset($topicModels[5])) {
//                    $temp = array_splice($topicModels, 6, 3);
//                    if (count($temp) == 1)
//                        $topicOrders[] = Utility::SingleMixTopic('single', $inner);
//                    else
//                        $topicOrders[] = Utility::SingleMixTopic('mix', $temp);
//                }
//            }
//
//            if ($key > 7 && $key <= 10) {
//                $topicOrders[] = Utility::SingleMixTopic('single', $inner);
//            }
//        }
//
//        return $topicOrders;
//    }


    private function getLowestAttemptedTopic($attempted_topics, $studentID)
    {
        $least_attempted_topic = array();
        foreach ($attempted_topics as $attempted_topic) {
            if (empty($least_attempted_topic)) {
                $least_attempted_topic = $attempted_topic;
            }

            if ($least_attempted_topic['score'] >= $attempted_topic['score']) {
                $least_attempted_topic = $attempted_topic;
            }
        }

        return $least_attempted_topic;
    }

    private function dailyRecommendationInitial($student = 32)
    {
        $school_id = StudentSchool::find()
            ->select(['school_id', 'class_id', 'student_id'])
            ->where(['student_id' => $student])
            ->asArray()
            ->one();

        if (!$school_id) {
            $classID = User::findOne(['id' => $student])->class;
        } else {
            $classID = Classes::findOne(['id' => $school_id['class_id']])->global_class_id;
        }

        //Daily_recommended_topics is to store the topics that are already exist in daily_recommendation
        $daily_recommended_topics = ArrayHelper::getColumn(
            RecommendationTopics::find()
                ->innerJoin('recommendations', 'recommendations.id = recommendation_topics.recommendation_id')
                ->where('WEEKDAY(recommendations.created_at) = ' . SharedConstant::VALUE_SIX)
                ->andWhere(['recommendation_topics.student_id' => $student])
                ->groupBy('recommendation_topics.object_id')
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
                    'subject_topics.class_id',
                    'subjects.name as subject_name',
                    'subjects.id as subject_id',
                    new Expression("'practice' AS type"),
                ])
                ->innerJoin('subjects', 'subjects.id = subject_topics.subject_id')
                ->where([
                    'subject_topics.subject_id' => $subject,
                    'subject_topics.class_id' => $classID
                ])
                ->andWhere(['NOT IN', 'subject_topics.id', $daily_recommended_topics])
                ->asArray()
                ->limit(SharedConstant::VALUE_ONE)
                ->all();

            if (sizeof($this->daily_recommended_topics) == SharedConstant::VALUE_THREE) {
                break;
            }

            $this->daily_recommended_topics = array_merge($this->daily_recommended_topics, $model);
        }

        if (!$this->daily_recommended_topics) {
            $this->startRecommendationsAgain($subjects, $classID);
        }

        $daily_recommended_videos = VideoContent::find()
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->where([
                'video_assign.topic_id' => ArrayHelper::getColumn(
                    $this->daily_recommended_topics, 'id')
            ])
            ->limit(SharedConstant::VALUE_TWO)
            ->all();

//        if (count($this->daily_recommended_topics) < SharedConstant::VALUE_THREE) {
//            $this->randomDailyRecommendationTopics($this->daily_recommended_topics, $subjects, $daily_recommended_topics, 'daily');
//        }

        return $this->topics = array_merge($this->daily_recommended_topics, $daily_recommended_videos);
        //$this->createRecommendations($this->topics, $student, SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ONE]);
    }

    private function startRecommendationsAgain($subjects, $classID)
    {
        foreach ($subjects as $subject) {
            $model = SubjectTopics::find()
                ->select([
                    'subject_topics.id',
                    'subject_topics.topic',
                    'subject_topics.week_number',
                    'subject_topics.term',
                    'subject_topics.class_id',
                    'subjects.name as subject_name',
                    'subjects.id as subject_id',
                    new Expression("'practice' AS type"),
                ])
                ->innerJoin('subjects', 'subjects.id = subject_topics.subject_id')
                ->where([
                    'subject_topics.subject_id' => $subject,
                    'subject_topics.class_id' => $classID
                ])
                ->asArray()
                ->limit(SharedConstant::VALUE_ONE)
                ->all();

            if (sizeof($this->daily_recommended_topics) == SharedConstant::VALUE_THREE) {
                break;
            }

            $this->daily_recommended_topics = array_merge($this->daily_recommended_topics, $model);
        }
    }

    private function randomDailyRecommendationTopics($recommended_topics, $subjects, $weekly_recommended_topics)
    {
        $recommended_subjects = ArrayHelper::getColumn($recommended_topics, 'subject_id');
        if (count($recommended_topics) == SharedConstant::VALUE_TWO) {
            foreach ($subjects as $subject) {
                $this->selectTopic($subject, $weekly_recommended_topics, $recommended_subjects, 'daily');
                if (count($this->daily_recommended_topics) == SharedConstant::VALUE_THREE) {
                    break;
                }
            }
        } elseif (count($this->daily_recommended_topics) == SharedConstant::VALUE_ONE) {
            foreach ($subjects as $subject) {
                $this->selectTopic($subject, $weekly_recommended_topics, $recommended_subjects, 'daily');
                if (count($this->daily_recommended_topics) == SharedConstant::VALUE_THREE) {
                    break;
                }
            }
        } else {
            foreach ($subjects as $subject) {
                $this->selectTopic($subject, $weekly_recommended_topics, $recommended_subjects, 'daily');
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
        } elseif (count($recommended_topics) == SharedConstant::VALUE_ONE) {
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
                'subject_topics.class_id',
                'subjects.name as subject_name',
                'subjects.id as subject_id',
                new Expression("'practice' AS type"),
            ])
            ->innerJoin('subjects', 'subjects.id = subject_topics.subject_id')
            ->where([
                'subject_topics.class_id' => $classID
            ])
            ->andWhere(['OR', ['subject_topics.subject_id' => $subject], ['subject_topics.subject_id' => $recommended_subjects]])
            ->andWhere(['NOT IN', 'subject_topics.id', $weekly_recommended_topics])
            ->asArray();

        if ($type == 'weekly') {
            $model = $model->andwhere([
                'subject_topics.term' => strtolower($term)
            ])->andWhere(['NOT IN', 'subject_topics.id', ArrayHelper::getColumn($this->weekly_recommended_topics, 'id')])
                ->andWhere(['OR', ['<=', 'subject_topics.week_number', $week], ['>', 'subject_topics.week_number', $week]]);
        }

        $model = $model->limit(SharedConstant::VALUE_THREE)
            ->all();

        if ($type == 'weekly') {
            $this->weekly_recommended_topics = array_merge($this->weekly_recommended_topics, $model);
        } else {
            $this->daily_recommended_topics = array_merge($this->daily_recommended_topics, $model);
        }
        array_splice($this->weekly_recommended_topics, 3);
    }


}

