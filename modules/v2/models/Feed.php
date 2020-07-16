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
			[['type', 'class_id', 'description'], 'required'],
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
			'user_id',
			'reference_id',
			'subject_id',
			'description',
			'type',
			'likes',
			'token',
			'class_id',
			'global_class',
			'view_by'
		];
	}

	public function beforeSave($insert) {
		if ($this->isNewRecord) {
			$this->token = GenerateString::widget();
			$this->created_at = date('y-m-d H-i-s');
			$this->updated_at = date('y-m-d H-i-s');
		} else {
			$this->updated_at = date('y-m-d H-i-s');
		}
		return parent::beforeSave($insert);
	}
}
