<?php
namespace app\modules\v2\student\models;

use Yii;
use yii\base\Model;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\{
    Homeworks,
    QuizSummary,
    HomeworkQuestions,
    PracticeTopics
};

/**
 * Password reset request form
 */
class StartQuizSummaryForm extends Model {
    public $student_id;
    public $practice_id;
    private $total_questions;

    public function rules() {
        return [
            [['student_id', 'practice_id'], 'required'],
            [['student_id', 'practice_id'], 'integer'],
            [['practice_id'], 'exist', 'targetClass' => Homeworks::className(), 'targetAttribute' => ['practice_id' => 'id', 'student_id' => 'student_id']],
        ];
    }

    public function startPractice()
    {
        foreach ($this->topicIds as $topicId) {
            $model = new QuizSummary;
            $model->homework_id = $this->practice_id;
            $model->subject_id = $this->homework->subject_id;
            $model->student_id = $this->homework->student_id;
            $model->teacher_id = $this->homework->teacher_id;
            $model->class_id = $this->homework->class_id;
            $model->type = $this->homework->type;
            $model->total_questions = $this->totalQuestions;
            $model->topic_id = $topicId;
            if (!$model->save(false)) {
                return false;
            }
        }

        return [
            'questions' => $this->total_questions,
        ];

    }

    public function getHomework()
    {
        return Homeworks::findOne(['id' => $this->practice_id]);
    }

    public function getTopicIds()
    {
        return PracticeTopics::find()->select('topic_id')->where(['practice_id' => $this->practice_id])->asArray()->all();
    }

    public function getTotalQuestions()
    {
        $this->total_questions = HomeworkQuestions::findAll(['homework_id' => $this->practice_id]);

        return count($this->total_questions);
    }
}
