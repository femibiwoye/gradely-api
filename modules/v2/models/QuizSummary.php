<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "quiz_summary".
 *
 * @property int $id
 * @property int $homework_id
 * @property int $subject_id
 * @property int $student_id
 * @property int|null $teacher_id
 * @property int $class_id
 * @property string $type it is either homework or catchup
 * @property int $total_questions
 * @property int|null $correct
 * @property int|null $failed
 * @property int|null $skipped
 * @property string $term
 * @property string $created_at
 * @property string|null $submit_at
 * @property int $submit
 * @property int $topic_id
 *
 * @property User $student
 * @property QuizSummaryDetails[] $quizSummaryDetails
 */
class QuizSummary extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'quiz_summary';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['homework_id', 'subject_id', 'student_id', 'class_id', 'total_questions', 'term', 'topic_id'], 'required'],
            [['homework_id', 'subject_id', 'student_id', 'teacher_id', 'class_id', 'total_questions', 'correct', 'failed', 'skipped', 'submit', 'topic_id'], 'integer'],
            [['type', 'term'], 'string'],
            [['created_at'], 'safe'],
            [['submit_at'], 'string', 'max' => 50],
            [['student_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'homework_id' => 'Homework ID',
            'subject_id' => 'Subject ID',
            'student_id' => 'Student ID',
            'teacher_id' => 'Teacher ID',
            'class_id' => 'Class ID',
            'type' => 'Type',
            'total_questions' => 'Total Questions',
            'correct' => 'Correct',
            'failed' => 'Failed',
            'skipped' => 'Skipped',
            'term' => 'Term',
            'created_at' => 'Created At',
            'submit_at' => 'Submit At',
            'submit' => 'Submit',
            'topic_id' => 'Topic ID',
        ];
    }

    /**
     * Gets query for [[Student]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudent()
    {
        return $this->hasOne(User::className(), ['id' => 'student_id']);
    }

    /**
     * Gets query for [[QuizSummaryDetails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuizSummaryDetails()
    {
        return $this->hasMany(QuizSummaryDetails::className(), ['quiz_id' => 'id']);
    }
}
