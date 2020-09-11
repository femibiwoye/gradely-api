<?php

namespace app\modules\v2\student\models;

use app\modules\v2\models\HomeworkQuestions;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\components\SharedConstant;

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
            'questions',
            'recommendations'
        ];
    }


    public function getCountAttemptedQuestions()
    {
        return HomeworkQuestions::find()->where(['homework_id' => $this->homework_id])->count();
    }

    public function getHomeworkScore()
    {
        if ($this->countAttemptedQuestions == SharedConstant::VALUE_ZERO) {
            return SharedConstant::VALUE_ZERO;
        }

        return rount(($this->correct / $this->countAttemptedQuestions) * 100);
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
                'q.image',
                'q.type',
                'q.difficulty',
                'q.duration',
                'q.explanation',
                '(case when qsd.selected = qsd.answer then 1 else 0 end) as correctStatus',
                'qsd.selected',
                'qsd.answer',
                'qsd.selected',
            ])
            ->where(['hq.homework_id'=>$this->homework_id])
            ->innerJoin('questions q','q.id = hq.question_id')
            ->leftJoin('quiz_summary_details qsd','qsd.question_id = q.id')
            ->asArray()
            ->all();
    }

    public function getRecommendations()
    {
        return null;
    }





}
