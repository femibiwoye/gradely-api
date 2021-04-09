<?php

namespace app\modules\v2\sms\models;

use Yii;

/**
 * This is the model class for table "teacher_class_subjects".
 *
 * @property int $id
 * @property int $School_id
 * @property int $teacher_id
 * @property int $class_subjects_id
 * @property string|null $modified_at
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
            [['School_id', 'teacher_id', 'class_subjects_id'], 'required'],
            [['School_id', 'teacher_id', 'class_subjects_id'], 'integer'],
            [['modified_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'School_id' => 'School ID',
            'teacher_id' => 'Teacher ID',
            'class_subjects_id' => 'Class Subjects ID',
            'modified_at' => 'Modified At',
        ];
    }
}
