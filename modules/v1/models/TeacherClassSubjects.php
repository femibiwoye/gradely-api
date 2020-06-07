<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "teacher_class_subjects".
 *
 * @property int $id
 * @property int $teacher_id
 * @property int $subject_id
 * @property int $class_id
 * @property int $school_id
 * @property int $status
 * @property string $created_at
 */
class TeacherClassSubjects extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'teacher_class_subjects';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'subject_id', 'class_id', 'school_id'], 'required'],
            [['teacher_id', 'subject_id', 'class_id', 'school_id', 'status'], 'integer'],
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
            'subject_id' => 'Subject ID',
            'class_id' => 'Class ID',
            'school_id' => 'School ID',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
