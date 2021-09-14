<?php

namespace app\modules\v2\teacher\models;

use app\modules\v2\models\Homeworks;
use app\modules\v2\models\QuizSummary;
use Yii;
use yii\base\Model;
use app\modules\v2\models\{Questions, HomeworkQuestions};

class HomeworkQuestionsForm extends Model
{
    public $questions;
    public $class_id;
    public $subject_id;
    public $homework_id;
    public $HomeworkQuestionModels = [];

    public function rules()
    {
        return [
            [['questions', 'class_id', 'subject_id', 'homework_id'], 'required'],
            [['class_id', 'subject_id', 'homework_id'], 'integer'],
            ['questions', 'each', 'rule' => ['integer']],
            ['questions', 'validateQuestion'],
        ];
    }

    public function validateQuestion()
    {
        if (!is_array($this->questions)) {
            $this->addError('questions', 'Questions need to be array of IDs');
            return false;
        }

        if (!Homeworks::find()->where(['id' => $this->homework_id, 'teacher_id' => Yii::$app->user->id])->exists()) {
            $this->addError('questions', 'You do not have access to this homework');
            return false;
        }

        if (QuizSummary::find()->where(['homework_id' => $this->homework_id, 'type' => 'homework', 'submit' => 1])->exists()) {
            $this->addError('questions', 'You cannot add because an attempt has been made on this homework.');
            return false;
        }


        $question_records = (int) Questions::find()->where(['id' => $this->questions, 'subject_id' => $this->subject_id])->count();
        if (count($this->questions) > $question_records) {
            $this->addError('questions', 'One or more questions is invalid');
            return false;
        }

        return true;
    }

    public function saveHomeworkQuestion()
    {
        if (!$this->deleteQuestion(1, $this->homework_id))
            return false;

        foreach ($this->questions as $question) {
            if (!$this->saveQuestion($question)) {
                return false;
            }
        }

        return true;
    }

    public function deleteQuestion($type = 0, $homework_id, $question_id = null)
    {
        $model = HomeworkQuestions::find()
            ->where(['homework_id' => $homework_id, 'teacher_id' => Yii::$app->user->id]);
        if ($type == 1) {
            if ($model->exists()) {
                return HomeworkQuestions::deleteAll(['homework_id' => $homework_id, 'teacher_id' => Yii::$app->user->id]);
            }
        } else {
            $model = $model->andWhere(['question_id' => $question_id]);
            if ($model->exists()) {
                return $model->one()->delete();
            }
        }

        return true;
    }

    public function saveQuestion($question_id)
    {
        $question = Questions::find()->where(['id' => $question_id])->one();
        $model = new HomeworkQuestions;
        $model->teacher_id = Yii::$app->user->id;
        $model->homework_id = $this->homework_id;
        $model->question_id = $question->id;
        $model->difficulty = $question->difficulty;
        $model->duration = $question->duration;
        if (!$model->save()) {
            return false;
        }

        array_push($this->HomeworkQuestionModels, $model);
        return true;
    }
}
