<?php

namespace app\modules\v2\models;

use Yii;

class Subjects extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'subjects';
    }

    public function rules()
    {
        return [
            [['slug', 'name', 'description'], 'required'],
            [['description', 'category'], 'string'],
            [['status'], 'integer'],
            [['created_at'], 'safe'],
            [['slug', 'name'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'slug' => 'Slug',
            'name' => 'Name',
            'description' => 'Description',
            'category' => 'Category',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    public function getClassSubjects()
    {
        return $this->hasMany(ClassSubjects::className(), ['subject_id' => 'id']);
    }

    public function getSchoolSubjects()
    {
        return $this->hasMany(SchoolSubject::className(), ['subject_id' => 'id']);
    }

    public function getTeacherClassSubjects()
    {
        return $this->hasMany(TeacherClassSubjects::className(), ['subject_id' => 'id']);
    }
}
