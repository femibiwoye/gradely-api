<?php

namespace app\modules\v2\models;

use Yii;

class SchoolTeachers extends \yii\db\ActiveRecord
{
	public static function tableName()
	{
		return 'school_teachers';
	}

	public function rules()
	{
		return [
			[['teacher_id', 'school_id'], 'required'],
			[['teacher_id', 'school_id', 'status'], 'integer'],
			[['created_at'], 'safe'],
			[['school_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
			[['teacher_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['teacher_id' => 'id']],
		];
	}

	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'teacher_id' => 'Teacher ID',
			'school_id' => 'School ID',
			'status' => 'Status',
			'created_at' => 'Created At',
		];
	}

	public function getSchool()
	{
		return $this->hasOne(Schools::className(), ['id' => 'school_id']);
	}

	public function getTeacher()
	{
		return $this->hasOne(User::className(), ['id' => 'teacher_id']);
	}
}
