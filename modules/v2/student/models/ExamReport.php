<?php

namespace app\modules\v2\student\models;

use app\modules\v2\components\Utility;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\HomeworkQuestions;
use app\modules\v2\models\Homeworks;
use app\modules\v2\models\PracticeTopics;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\QuizSummaryDetails;
use app\modules\v2\models\SubjectTopics;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class ExamReport extends QuizSummary
{

    public function fields()
    {
        return [
            'id',
            'homework_id',
            'subject_id',
            'student_id',
            'teacher_id',
            'class_id',
            'total_questions',
            'correct',
            'failed',
            'skipped',
            'submit',
            'type',
            'topic_id',
            'subject' => 'homeworkSubject',
            'exam',
            'actualAttemptCount' => 'countAttemptedQuestions',
            'actualScore' => 'homeworkScore',
            'homework_title' => 'homeworkTitle',
            'topics',
            'questions',
            'recommendations',
            'timeSpent',
            'gradeIndicator',
            'averageTime',

        ];
    }


    public function getCountAttemptedQuestions()
    {
        return HomeworkQuestions::find()->where(['homework_id' => $this->homework_id])->count();
    }

    public function getHomework()
    {
        return Homeworks::find()->where(['id' => $this->homework_id])->one();
    }

    public function getHomeworkTitle()
    {
        $homework = $this->getHomework();
        return isset($homework->title) ? $homework->title : null;
    }

    public function getHomeworkSubject()
    {
        $homework = $this->getHomework();
        return isset($homework->title) ? $homework->subject->name : null;
    }

    public function getHomeworkScore()
    {
        if ($this->countAttemptedQuestions == SharedConstant::VALUE_ZERO) {
            return SharedConstant::VALUE_ZERO;
        }

        return $this->countAttemptedQuestions > 0 ? round(($this->correct / $this->countAttemptedQuestions) * 100) : 0;
    }

    public function getQuestions()
    {
        return HomeworkQuestions::find()
            ->alias('hq')
            ->select([
                'q.id question_id',
                'q.question',
                'q.option_a',
                'q.option_b',
                'q.option_c',
                'q.option_d',
                'q.answer',
                //'q.image',
                \Yii::$app->params['questionImage'],
                'q.type',
                'q.difficulty',
                'q.duration',
                'q.explanation',
                '(case when qsd.selected = q.answer then 1 else 0 end) as correctStatus',
                'qsd.selected',
                'qsd.time_spent',
            ])
            ->innerJoin('questions q', 'q.id = hq.question_id')
            ->leftJoin('quiz_summary_details qsd', 'qsd.question_id = q.id AND qsd.homework_id = ' . $this->homework_id . ' AND qsd.student_id = ' . $this->student_id)
            ->where(['hq.homework_id' => $this->homework_id])
            ->groupBy('hq.question_id')
            ->asArray()
            ->all();
    }

    public function getTopics()
    {
        //$topics = PracticeTopics::find()->select(['topic_id'])->where(['practice_id'=>$this->homework_id])->all();
        //ArrayHelper::getColumn($topics, 'topic_id')
        return SubjectTopics::find()
            ->alias('st')
            ->select([
                'st.id',
                'topic',
                'slug',
                new Expression('COUNT(case when qsd.topic_id = st.id AND hq.homework_id = ' . $this->homework_id.' then 1 else 0 end) as questionCount'),
                new Expression('SUM(case when qsd.selected = qsd.answer AND qsd.topic_id = st.id then 1 else 0 end) as questionCorrect'),
                new Expression('round((SUM(case when qsd.selected = qsd.answer AND qsd.topic_id = st.id then 1 else 0 end)/COUNT(case when qsd.topic_id = st.id then 1 else 0 end))*100) as score')
            ])
            ->innerJoin('practice_topics pt', 'pt.topic_id = st.id')
            ->innerJoin('homework_questions hq', 'hq.homework_id = pt.practice_id')
            ->innerJoin('questions q', 'q.id = hq.question_id AND hq.homework_id = ' . $this->homework_id)
            ->leftJoin('quiz_summary_details qsd', 'qsd.homework_id = pt.practice_id AND qsd.homework_id = ' . $this->homework_id)
            ->where(['pt.practice_id' => $this->homework_id])
            ->groupBy('st.id')
            ->asArray()
            ->all();
    }

    public function getRecommendations()
    {
        return null;
    }

    public function getExam()
    {
        return ExamType::find()
            ->select(['exam_type.id', 'name', 'exam_type.title'])
            ->innerJoin('homeworks', 'homeworks.id = ' . $this->homework_id . ' AND exam_type.id = homeworks.exam_type_id')
            //->groupBy('exam_type.id')
            ->one();
    }

    public function getTimeSpent()
    {
        return strtotime($this->submit_at) - strtotime($this->getHomework()->created_at);
    }

    public function getGradeIndicator()
    {
        return Utility::GradeIndicator($this->homeworkScore);
    }

    public function getAverageTime()
    {
        $model = ArrayHelper::getColumn(QuizSummaryDetails::find()->select(['time_spent'])->where(['quiz_id' => $this->id])->all(), 'time_spent');

        return array_sum($model) / count($model);
    }
}
