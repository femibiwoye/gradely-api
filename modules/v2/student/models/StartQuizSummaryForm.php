<?php

namespace app\modules\v2\student\models;

use app\modules\v2\components\Utility;
use app\modules\v2\models\Questions;
use Yii;
use yii\base\Model;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\{
    Homeworks,
    QuizSummary,
    HomeworkQuestions,
    PracticeTopics
};
use yii\helpers\ArrayHelper;

/**
 * Password reset request form
 */
class StartQuizSummaryForm extends Model
{
    public $student_id;
    public $practice_id;
    private $total_questions;

    public function rules()
    {
        return [
            [['student_id', 'practice_id'], 'required'],
            [['student_id', 'practice_id'], 'integer'],
            [['practice_id'], 'exist', 'targetClass' => Homeworks::className(), 'targetAttribute' => ['practice_id' => 'id', 'student_id' => 'student_id']],
        ];
    }

    public function startPractice()
    {

        $model = QuizSummary::find()->where(['homework_id' => $this->practice_id, 'student_id' => Yii::$app->user->id]);
        if ($model->exists()) {
            $model = $model->one();
            $questionsID = ArrayHelper::getColumn($model->homeworkQuestions, 'question_id');
            return array_merge(ArrayHelper::toArray($model->childHomework), ['questions' => Questions::find()->where(['id' => $questionsID])->all()]);
        }

        //foreach ($this->topicIds as $topicId) {
        $model = new QuizSummary;
        $model->homework_id = $this->practice_id;
        $model->subject_id = $this->homework->subject_id;
        $model->student_id = Yii::$app->user->id;
        $model->teacher_id = $model->student_id;
        $model->class_id = $this->homework->class_id;
        $model->type = $this->homework->type;
        $model->total_questions = $this->totalQuestions;
        $termWeek = Utility::getStudentTermWeek();
        $model->term = $termWeek['term'];
        //$model->topic_id = $topicId;
        if (!$model->save()) {
            return false;
        }
        // }

        $questionsID = ArrayHelper::getColumn($model->homeworkQuestions, 'question_id');
        return array_merge(ArrayHelper::toArray($model->childHomework), ['questions' => Questions::find()->where(['id' => $questionsID])->all()]);

//        $questionsID = ArrayHelper::getColumn($model->one()->homeworkQuestions, 'question_id');
//        return Questions::find()->where(['id' => $questionsID])->all();;

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
        return HomeworkQuestions::find()->where(['homework_id' => $this->practice_id])->count();
    }
}
