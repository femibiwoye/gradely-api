<?php

namespace app\modules\v2\models;

use app\modules\v2\components\StudentAnalytics;
use Yii;
use app\modules\v2\components\SharedConstant;
use yii\base\Model;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Password reset request form
 */
class StudentDetails extends User
{

    public function fields()
    {
        return [
            'id',
            'firstname',
            'lastname',
            'code',
            'image',
            'email',
            'phone',
            'type',
            'profile' => 'userProfile',
            'remarks' => 'remarks',
            'topics' => 'topicBreakdown',
            'homework',
            'completion_rate' => 'totalHomeworks',
            'positions' => 'statistics',
            'performance' => 'performance'
        ];
    }

    public function getUser()
    {
        return $this;
    }

    public function getSummary()
    {
        return ['positions' => $this->getStatistics(), 'performance' => $this->getPerformance()];
    }

    public function getHomework()
    {

        return QuizSummary::find()
            ->alias('q')
            ->select(['q.*', '(q.correct/q.total_questions)*100 score'])
            ->where(['q.student_id' => $this->id])
            ->joinWith(['childHomework', 'subject'])
            ->asArray()
            ->all();
    }

    public function getRemarks()
    {
        return Remarks::find()
            ->where(['receiver_id' => $this->id, 'type' => 'student'])
            ->asArray()
            ->all();
    }

    public function getTotalHomeworks()
    {

        $class = StudentSchool::findOne(['student_id' => $this->id, 'status' => 1]);

        $homeworkCount = Homeworks::find()
            ->where(['teacher_id' => Yii::$app->user->id, 'class_id' => $class->class_id, 'status' => 1])
            ->count();

        $studentCount = QuizSummary::find()
            ->alias('q')
            ->where(['q.student_id' => $this->id, 'q.class_id' => $class->class_id, 'submit' => 1])
            ->innerJoin('homeworks', "homeworks.id = q.homework_id AND homeworks.id = 1 AND homeworks.teacher_id = " . Yii::$app->user->id)
            ->count();

        return $homeworkCount > 0 ? $studentCount / $homeworkCount * 100 : 0;

        $attempted_questions = 0;
        foreach ($this->homework as $homework) {
            if ($homework->quizSummary && $homework->quizSummary->submit == SharedConstant::VALUE_ONE) {
                $attempted_questions = $attempted_questions + 1;
            }
        }

        if ($attempted_questions > 0)
            $attempted_questions = ($attempted_questions / count($this->homework)) * 100;


        return $attempted_questions;
    }

    public function getTopicBreakdown()
    {
        $topics = QuizSummaryDetails::find()
            ->alias('s')
            ->select(['s.topic_id'])
            ->where(['s.student_id' => $this->id])
            ->innerJoin('quiz_summary q', 'q.id = s.quiz_id AND q.submit = 1')
            ->groupBy('topic_id')
            ->asArray()
            ->all();

        $groupPerformance = [];
        foreach ($topics as $topic) {
            $summary = QuizSummaryDetails::find()
                ->where(['topic_id' => $topic, 'student_id' => $this->id]);

            $totalAttempt = $summary->count();
            $correctAttempt = $summary->andWhere(['=', 'selected', new Expression('`answer`')])->count();
            $topicScore = ($correctAttempt / $totalAttempt) * 100;

            $statistics = ['score' => $topicScore, 'attempted' => $totalAttempt, 'correct' => $correctAttempt, 'improvement' => null, 'direction' => null]; //Direction is up|down
            $topicDetails = SubjectTopics::findOne(['id' => $topic]);

            $groupPerformance[] = array_merge(ArrayHelper::toArray($topicDetails), ['stastistics' => $statistics]);
        }
        return $groupPerformance;
    }

    public function getStatistics()
    {
        $studentAnalytics = new StudentAnalytics();
        return $result = $studentAnalytics->Analytics($this);

    }

    public function checkStudentInTeacherClass()
    {
        $teacher_classes = TeacherClass::find()->where(['teacher_id' => Yii::$app->user->id])->all();
        foreach ($teacher_classes as $teacher_class) {
            if (StudentSchool::find()->where(['class_id' => $teacher_class->class_id])->andWhere(['student_id' => $this->id])->one()) {
                return true;
            }
        }

        return false;
    }

    public function getPerformance()
    {
        $excellence = [];
        $averages = [];
        $struggling = [];

        $activeTopics = QuizSummaryDetails::find()->select(['topic_id'])->where(['student_id' => $this->id]);


        if (isset($_GET['term']))
            $activeTopics = $activeTopics->andWhere(['term' => isset($_GET['term'])]);
        $activeTopics = ArrayHelper::getColumn($activeTopics->groupBy('topic_id')->all(), 'topic_id');

        if (!empty($activeTopics)) {

            if (isset($_GET['subject'])) {
                $selectedSubject = Subjects::find()->where(['status' => 1]);
                $selectedSubject = $selectedSubject->andWhere(['slug' => $_GET['subject']]);
                $selectedSubject = ArrayHelper::getColumn($selectedSubject->all(), 'id');
            } else {
                $attemptedSubjects = QuizSummary::find()->where(['student_id' => $this->id])->groupBy('subject_id')->all();
                $selectedSubject = ArrayHelper::getColumn($attemptedSubjects, 'subject_id');
            }

            $topics = SubjectTopics::find()
                ->where([
                    'subject_id' => $selectedSubject,
                    'id' => $activeTopics
                ])
                ->all();

            if (!empty($topics)) {
                foreach ($topics as $data) {
                    if ($data->getTopicPerformanceByID($data->id, $this->id) >= 75) {
                        $excellence[] = $this->topicPerformanceMini($data);
                    } elseif ($data->getTopicPerformanceByID($data->id, $this->id) >= 50 && $data->getTopicPerformanceByID($data->id, $this->id) < 75) {
                        $averages[] = $this->topicPerformanceMini($data);
                    } elseif ($data->getTopicPerformanceByID($data->id, $this->id) < 50) {
                        $struggling[] = $this->topicPerformanceMini($data);
                    }
                }
            }
        }

        $excellence = array_slice($excellence, 0, 5);
        $averages = array_slice($averages, 0, 5);
        $struggling = array_slice($struggling, 0, 5);

        return [
            'excellence' => $excellence,
            'average' => $averages,
            'struggling' => $struggling
        ];

    }

    private function topicPerformanceMini($data)
    {
        return ['title' => $data->topic, 'id' => $data->id];
    }
}
