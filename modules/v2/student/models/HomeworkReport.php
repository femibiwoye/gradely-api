<?php

namespace app\modules\v2\student\models;

use app\modules\v2\models\HomeworkQuestions;
use app\modules\v2\models\Homeworks;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\SubjectTopics;

class HomeworkReport extends QuizSummary
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
            'topic_id',
            'actualAttemptCount' => 'countAttemptedQuestions',
            'actualScore' => 'homeworkScore',
            'homework_title' => 'homeworkTitle',
            'questions',
            'recommendations'
        ];
    }


    public function getCountAttemptedQuestions()
    {
        return HomeworkQuestions::find()->where(['homework_id' => $this->homework_id])->count();
    }

    public function getHomeworkTitle()
    {
        $homework = Homeworks::find()->where(['id' => $this->homework_id])->one();
        return isset($homework->title) ? $homework->title : null;
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
                'q.image',
                'q.type',
                'q.difficulty',
                'q.duration',
                'q.explanation',
                '(case when qsd.selected = qsd.answer then 1 else 0 end) as correctStatus',
                'qsd.selected',
                'qsd.selected',
            ])
            ->where(['hq.homework_id' => $this->homework_id,'q.student_id'=>$this->student_id])
            ->innerJoin('questions q', 'q.id = hq.question_id')
            ->leftJoin('quiz_summary_details qsd', 'qsd.question_id = q.id')
            ->asArray()
            ->all();
    }

    public function getRecommendations()
    {
        return null;
    }


}
