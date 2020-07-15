<?php

namespace app\modules\v2\models;

use Yii;

class FeedComment extends \yii\db\ActiveRecord
{
	public static function tableName()
	{
		return 'feed_comment';
	}

	public function rules()
	{
		return [
			[['feed_id', 'user_id', 'comment'], 'required'],
			[['feed_id', 'user_id', 'status'], 'integer'],
			[['comment'], 'string'],
			[['created_at', 'updated_at'], 'safe'],
			[['feed_id'], 'exist', 'skipOnError' => true, 'targetClass' => Feed::className(), 'targetAttribute' => ['feed_id' => 'id']],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
		];
	}

	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'feed_id' => 'Feed ID',
			'user_id' => 'User ID',
			'comment' => 'Comment',
			'status' => 'Status',
			'created_at' => 'Created At',
			'updated_at' => 'Updated At',
		];
	}

	public function fields() {
		return [
			'id',
			'feed_id',
			'user_id',
			'comment',
		];
	}

	public function getFeed()
	{
		return $this->hasOne(Feed::className(), ['id' => 'feed_id']);
	}

	public function getUser()
	{
		return $this->hasOne(User::className(), ['id' => 'user_id']);
	}

	public function beforeSave($insert) {
		if ($this->isNewRecord) {
			$this->created_at = date('y-m-d H-i-s');
			$this->updated_at = date('y-m-d H-i-s');
		} else {
			$this->updated_at = date('y-m-d H-i-s');
		}
		return parent::beforeSave($insert);
	}
}
