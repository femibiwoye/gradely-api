<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

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
			[['comment','type'], 'string'],
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
            'likesCount' => 'feedLikeCount',
            'myLike',
            'user',
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

    public function getMyLike()
    {
        return $this->hasOne(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => SharedConstant::COMMENT_TYPE, 'user_id' => Yii::$app->user->id])->exists() ? 1 : 0;
    }

    public function getFeedLikeCount()
    {
        return $this->hasOne(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => SharedConstant::COMMENT_TYPE])->count();
    }

	public function getFeedCommentLike() {
		return $this->hasOne(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => SharedConstant::COMMENT_TYPE]);
	}

	public function FeedCommentDisliked() {
		$model = $this->getFeedCommentLike()->andWhere(['user_id' => Yii::$app->user->id])->one();
		if (!$model->delete()) {
			return false;
		}

		return true;
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
