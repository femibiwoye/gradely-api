<?php

namespace app\modules\v2\components;


use app\modules\v2\models\Classes;
use app\modules\v2\models\QuizSummaryDetails;
use app\modules\v2\models\Recommendations;
use app\modules\v2\models\RecommendationTopics;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\User;
use app\modules\v2\models\VideoContent;
use Yii;
use yii\base\Model;
use yii\db\Expression;
use yii\helpers\ArrayHelper;


class Recommendation extends Model
{

    public function dailyRecommendation($student)
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

        if ($classID) {
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
    }

    public function PracticeVideoRecommendation($topic_id, $student)
    {

        $currentWeekTerm = Utility::getStudentTermWeek(null, $student);
        $currentSubject = SubjectTopics::find()->select('subject_topics.id')
            ->where(['AND', ['subject_topics.term' => $currentWeekTerm['term']], ['>=', 'subject_topics.week_number', $currentWeekTerm['week']]])
            ->innerJoin('questions q','q.topic_id = subject_topics.id')
            ->having('count(q.id) >= 30')
            ->asArray()->one();

        $topic_id = ArrayHelper::getColumn($topic_id, 'topic_id');
        $topic_id = isset($currentSubject['id']) ? array_merge($topic_id, [$currentSubject['id']]) : $topic_id;

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
            ->limit(8)
            ->all();

        array_multisort(array_column($attempted_topic, 'score'), $attempted_topic);

        $newTopics = SubjectTopics::find()
            ->select([
                new Expression("0 as score"),
                'subject_topics.id',
                Utility::ImageQuery('subject_topics'),
                'topic',
                'subject_topics.subject_id',
                new Expression("'practice' as type"),
            ])
            ->where(['subject_topics.class_id' => Utility::getStudentClass(1,$student)])->andWhere(['NOT IN', 'subject_topics.id', $attempted_topic])
            ->innerJoin('questions q','q.topic_id = subject_topics.id')
            ->having('count(q.id) >= 30')
            ->asArray()->one();

        $attempted_topic_with_new = array_merge([$newTopics],$attempted_topic);

        $topicsOnly = ArrayHelper::getColumn($attempted_topic, 'id');

        $video = VideoContent::find()
            ->select([
                'video_content.*',
                new Expression("'video' as type"),
                'gc.id class_id',
                'gc.description class_name',
                'st.subject_id subject_id',
            ])
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->innerJoin('subject_topics st', 'st.id = video_assign.topic_id')
            ->innerJoin('global_class gc', 'gc.id = st.class_id')
            ->where(['video_assign.topic_id' => $topic_id]);
        if (!empty($topicsOnly)) {
            $videoIDs = implode(', ', ($topicsOnly));
            $video = $video->orderBy([new \yii\db\Expression("FIELD (video_assign.topic_id, $videoIDs)")]);
        }
        $video = $video->limit(SharedConstant::VALUE_TWO)
            ->asArray()
            ->all();

        $todayRecommendation = Adaptivity::GenerateStudentSingleMixVideoRecommendations($attempted_topic_with_new, $video);

        return $this->createRecommendations($todayRecommendation, $student, 'daily');
    }

    private function createRecommendations($recommendations, $student, $category = 'daily')
    {
        if (!empty($recommendations)) {
            $dbtransaction = Yii::$app->db->beginTransaction();
            try {
                $recommendedCount = Recommendations::find()->where(['student_id' => $student, 'category' => $category, 'is_taken' => 0])
                    ->andWhere(['IS NOT', 'raw', null])->count();
                if ($recommendedCount > 0) {

                    //Recommendations::updateAll(['created_at' => date('Y-m-d H:i:s')], ['AND', ['student_id' => $student, 'category' => $category, 'is_taken' => 0], ['IS NOT', 'raw', null]]);
                    Recommendations::deleteAll(['AND', ['student_id' => $student, 'category' => $category, 'is_taken' => 0], ['IS NOT', 'raw', null]]);
                    $recommendedCount = 0;
                }

                foreach ($recommendations as $recommendation) {
                    if ($recommendedCount >= 6)
                        break;
                    $model = new Recommendations;
                    $model->student_id = $student;
                    $model->category = $category;
                    $model->type = $recommendation['type'];
                    $model->resource_count = $recommendation['question_count'];
                    $model->raw = $recommendation;
                    if (isset($_GET['tomorrow']) && isset($_GET['tomorrow']) == 1) {
                        $model->created_at = date("Y-m-d", strtotime("+1 day"));
                    }
                    if (!$model->save()) {
                        return false;
                    }
                    if (!$this->createRecommendedTopics($recommendation, $model)) {
                        return false;
                    }
                    $recommendedCount++;
                }
                $dbtransaction->commit();
            } catch (\Exception $e) {
                $dbtransaction->rollBack();
                return false;
            }
            return true;
        }
    }

    private function createRecommendedTopics($objects, $recommendation)
    {


        if ($objects['type'] == 'single') {
            $objects = [$objects['topic']];
        } elseif ($objects['type'] == 'mix') {
            $objects = $objects['topic'];
        } else {
            $objects = [$objects];
        }
        foreach ($objects as $object) {
            $model = new RecommendationTopics;
            $model->recommendation_id = $recommendation->id;
            $model->subject_id = $object['subject_id'];
            $model->student_id = $recommendation->student_id;
            $model->object_id = $object['id'];
            $model->object_type = $object['type'];
            if (isset($_GET['tomorrow']) && isset($_GET['tomorrow']) == 1) {
                $model->created_at = date("Y-m-d", strtotime("+1 day"));
            }
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }
}