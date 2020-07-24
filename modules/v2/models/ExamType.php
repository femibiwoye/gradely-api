<?php

namespace app\modules\v2\models;

use Yii;

class ExamType extends \yii\db\ActiveRecord
{
	public static function tableName()
	{
		return 'exam_type';
	}

	public function rules()
	{
		return [
			[['slug', 'name', 'title', 'description'], 'required'],
			[['description'], 'string'],
			[['general'], 'integer'],
			[['created_at'], 'safe'],
			[['slug', 'name'], 'string', 'max' => 200],
			[['title'], 'string', 'max' => 100],
		];
	}

	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'slug' => 'Slug',
			'name' => 'Name',
			'title' => 'Title',
			'description' => 'Description',
			'general' => 'General',
			'created_at' => 'Created At',
		];
	}

	public function getSchoolCurriculums()
	{
		return $this->hasMany(SchoolCurriculum::className(), ['curriculum_id' => 'id']);
	}
}
