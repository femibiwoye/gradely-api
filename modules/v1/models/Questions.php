<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "questions".
 *
 * @property int $id
 * @property int|null $teacher_id
 * @property int|null $homework_id
 * @property int $subject_id
 * @property string|null $class_id
 * @property int|null $school_id
 * @property string $question
 * @property string $option_a
 * @property string $option_b
 * @property string $option_c
 * @property string $option_d
 * @property string|null $option_e
 * @property string $answer Answer should either be A, B, C, D, or E
 * @property int $topic_id
 * @property int $exam_type_id
 * @property string|null $image
 * @property string $difficulty
 * @property int $duration duration is in seconds
 * @property string|null $explanation
 * @property string|null $clue This give clue to the question
 * @property string $category
 * @property int $status
 * @property string $created_at
 */
class Questions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'questions';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'homework_id', 'subject_id', 'school_id', 'topic_id', 'exam_type_id', 'duration', 'status'], 'integer'],
            [['subject_id', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'answer', 'topic_id', 'exam_type_id', 'difficulty', 'duration'], 'required'],
            [['question', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'difficulty', 'explanation', 'clue', 'category'], 'string'],
            [['created_at'], 'safe'],
            [['class_id'], 'string', 'max' => 15],
            [['answer'], 'string', 'max' => 1],
            [['image'], 'string', 'max' => 200],
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
            'subject_id' => 'Subject ID',
            'class_id' => 'Class ID',
            'school_id' => 'School ID',
            'question' => 'Question',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'option_e' => 'Option E',
            'answer' => 'Answer',
            'topic_id' => 'Topic ID',
            'exam_type_id' => 'Exam Type ID',
            'image' => 'Image',
            'difficulty' => 'Difficulty',
            'duration' => 'Duration',
            'explanation' => 'Explanation',
            'clue' => 'Clue',
            'category' => 'Category',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
