<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "homework_questions".
 *
 * @property int $id
 * @property int $teacher_id
 * @property int $homework_id
 * @property int $question_id
 * @property string $difficulty
 * @property int $duration
 * @property string $created_at
 */
class HomeworkQuestions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'homework_questions';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'homework_id', 'question_id', 'difficulty', 'duration'], 'required'],
            [['teacher_id', 'homework_id', 'question_id', 'duration'], 'integer'],
            [['difficulty'], 'string'],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'teacher_id' => 'Teacher ID',
            'homework_id' => 'Homework ID',
            'question_id' => 'Question ID',
            'difficulty' => 'Difficulty',
            'duration' => 'Duration',
            'created_at' => 'Created At',
        ];
    }
}
