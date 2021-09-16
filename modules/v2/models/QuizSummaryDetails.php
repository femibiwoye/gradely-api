<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "quiz_summary_details".
 *
 * @property int $id
 * @property int $quiz_id
 * @property int $homework_id
 * @property int $student_id
 * @property int $question_id
 * @property int $time_spent
 * @property string $selected
 * @property string|null $answer
 * @property string|null $answer_attachment
 * @property int|null $topic_id
 * @property int|null $is_correct
 * @property int|null $score
 * @property string $created_at
 *
 * @property QuizSummary $quiz
 */
class QuizSummaryDetails extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'quiz_summary_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['quiz_id', 'homework_id', 'student_id', 'question_id', 'selected'], 'required'],
            [['quiz_id', 'homework_id', 'student_id', 'question_id', 'topic_id', 'time_spent', 'is_correct', 'score'], 'integer'],
            [['created_at', 'answer_attachment'], 'safe'],
//            [['selected', 'answer'], 'string', 'max' => 1],
            ['answer_attachment', 'url', 'defaultScheme' => 'https'],
            [['quiz_id'], 'exist', 'skipOnError' => true, 'targetClass' => QuizSummary::className(), 'targetAttribute' => ['quiz_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'quiz_id' => 'Quiz ID',
            'homework_id' => 'Homework ID',
            'student_id' => 'Student ID',
            'question_id' => 'Question ID',
            'selected' => 'Selected',
            'answer' => 'Answer',
            'topic_id' => 'Topic ID',
            'created_at' => 'Created At',
        ];
    }

//    public function fields()
//    {
//        return ['selectedAnswer', 'correctAnswer', 'quiz', 'question']; // TODO: Change the autogenerated stub
//    }

    /**
     * Gets query for [[Quiz]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuiz()
    {
        return $this->hasOne(QuizSummary::className(), ['id' => 'quiz_id']);
    }

    public function getQuestion()
    {
        return $this->hasOne(Questions::className(), ['id' => 'question_id']);
    }

    public function getSelectedAnswer()
    {

        $selected_answer = 'option_' . strtolower($this->answer);
        $question = Questions::findOne($this->question_id);

        return $question->$selected_answer;
    }

    public function getCorrectAnswer()
    {

        $correct_answer = 'option_' . strtolower($this->answer);
        $question = Questions::findOne($this->question_id);

        return $question->$correct_answer;
    }
}
