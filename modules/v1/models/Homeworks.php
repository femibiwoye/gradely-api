<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "homeworks".
 *
 * @property int $id
 * @property int $teacher_id
 * @property int $subject_id
 * @property int $class_id
 * @property int $school_id
 * @property int $exam_type_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property int $topic_id
 * @property int $curriculum_id
 * @property int $publish_status
 * @property string $access_status
 * @property string $open_date
 * @property string $close_date
 * @property int|null $duration Duration should be in minutes 
 * @property int $status
 * @property string $created_at
 */
class Homeworks extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'homeworks';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'slug', 'title', 'topic_id', 'curriculum_id', 'open_date', 'close_date'], 'required'],
            [['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'topic_id', 'curriculum_id', 'publish_status', 'duration', 'status'], 'integer'],
            [['description', 'access_status'], 'string'],
            [['open_date', 'close_date', 'created_at'], 'safe'],
            [['slug', 'title'], 'string', 'max' => 255],
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
            'subject_id' => 'Subject ID',
            'class_id' => 'Class ID',
            'school_id' => 'School ID',
            'exam_type_id' => 'Exam Type ID',
            'slug' => 'Slug',
            'title' => 'Title',
            'description' => 'Description',
            'topic_id' => 'Topic ID',
            'curriculum_id' => 'Curriculum ID',
            'publish_status' => 'Publish Status',
            'access_status' => 'Access Status',
            'open_date' => 'Open Date',
            'close_date' => 'Close Date',
            'duration' => 'Duration',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
