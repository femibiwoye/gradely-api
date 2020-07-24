<?php

namespace app\modules\v2\models;

use Yii;

class PracticeMaterial extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'practice_material';
	}

	public function rules()
	{
		return [
			[['user_id', 'title', 'filename', 'filetype', 'extension'], 'required'],
			[['practice_id', 'user_id', 'downloadable'], 'integer'],
			[['filetype', 'description'], 'string'],
			[['created_at', 'updated_at'], 'safe'],
			[['title', 'filename'], 'string', 'max' => 100],
			[['filesize', 'download_count', 'extension'], 'string', 'max' => 45],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
		];
	}

	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'practice_id' => 'Practice ID',
			'user_id' => 'User ID',
			'title' => 'Title',
			'filename' => 'Filename',
			'filetype' => 'Filetype',
			'description' => 'Description',
			'filesize' => 'Filesize',
			'downloadable' => 'Downloadable',
			'download_count' => 'Download Count',
			'extension' => 'Extension',
			'created_at' => 'Created At',
			'updated_at' => 'Updated At',
		];
	}

	public function getUser()
	{
		return $this->hasOne(User::className(), ['id' => 'user_id']);
	}
}
