<?php

namespace app\modules\v2\teacher\models;

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
        $question_records = Questions::find()->where(['id' => $this->questions, 'class_id' => $this->class_id, 'subject_id' => $this->subject_id])->count();
        if (count($this->questions) != $question_records) {
            $this->addError('Questions Ids are not correct');
        }

        return true;
    }

    public function saveHomeworkQuestion()
    {
        foreach ($this->questions as $question) {
            if (!$this->deleteQuestion($question) || !$this->saveQuestion($question)) {
                return false;
            }
        }

        return true;
    }

    public function deleteQuestion($question_id)
    {
        $model = HomeworkQuestions::find()
                    ->where(['question_id' => $question_id, 'teacher_id' => Yii::$app->user->identity->id])
                    ->one();

        if (!$model->delete()) {
            return false;
        }

        return true;
    }

    public function saveQuestion($question_id)
    {
        $question = Questions::findOne(['id' => $question_id]);
        $model = new HomeworkQuestions;
        $model->teacher_id = Yii::$app->user->identity->id;
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
