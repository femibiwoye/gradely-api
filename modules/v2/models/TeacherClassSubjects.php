<?php

namespace app\modules\v2\models;

use Yii;

class TeacherClassSubjects extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'teacher_class_subjects';
    }

    public function rules()
    {
        return [
            [['teacher_id', 'subject_id', 'class_id', 'school_id'], 'required'],
            [['teacher_id', 'subject_id', 'class_id', 'school_id', 'status'], 'integer'],
            [['created_at'], 'safe'],
            [['class_id'], 'exist', 'skipOnError' => true, 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
            [['teacher_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['teacher_id' => 'id']],
            [['subject_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']],
        ];
    }

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

    public function getClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id']);
    }

    public function getTeacher()
    {
        return $this->hasOne(User::className(), ['id' => 'teacher_id']);
    }

    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }
}
