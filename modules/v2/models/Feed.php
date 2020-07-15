<?php

namespace app\modules\v2\models;

use Yii;

class Feed extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'feed';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['user_id', 'token'], 'required'],
			[['user_id', 'reference_id', 'likes', 'class_id', 'global_class', 'status'], 'integer'],
			[['description', 'type', 'view_by'], 'string'],
			[['created_at', 'updated_at'], 'safe'],
			[['subject_id'], 'string', 'max' => 45],
			[['token'], 'string', 'max' => 100],
			[['token'], 'unique'],
			[['class_id'], 'exist', 'skipOnError' => true, 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
			[['global_class'], 'exist', 'skipOnError' => true, 'targetClass' => GlobalClass::className(), 'targetAttribute' => ['global_class' => 'id']],
			[['reference_id'], 'exist', 'skipOnError' => true, 'targetClass' => Homeworks::className(), 'targetAttribute' => ['reference_id' => 'id']],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
		];
	}

	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'user_id' => 'User ID',
			'reference_id' => 'Reference ID',
			'subject_id' => 'Subject ID',
			'description' => 'Description',
			'type' => 'Type',
			'likes' => 'Likes',
			'token' => 'Token',
			'class_id' => 'Class ID',
			'global_class' => 'Global Class',
			'view_by' => 'View By',
			'status' => 'Status',
			'created_at' => 'Created At',
			'updated_at' => 'Updated At',
		];
	}

	public function fields() {
		return [
			'id', 
			'type',
			'title',
			'date',
			'time'
		];
	}

	public function getTime() {
		return date('H:i:s', $this->updated_at);
	}

	public function getDate() {
		return date('m/d/Y', $this->updated_at);
	}
}
