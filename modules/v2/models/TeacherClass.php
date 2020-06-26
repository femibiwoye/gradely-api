<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "teacher_class".
 *
 * @property int $id
 * @property int $teacher_id
 * @property int $school_id
 * @property int $class_id
 * @property int $status
 * @property string $created_at
 */
class TeacherClass extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'teacher_class';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'school_id', 'class_id'], 'required'],
            [['teacher_id', 'school_id', 'class_id', 'status'], 'integer'],
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
            'school_id' => 'School ID',
            'class_id' => 'Class ID',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
