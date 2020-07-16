<?php

namespace app\modules\v2\models;

use Yii;

class FeedLike extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'feed_like';
    }

    public function rules()
    {
        return [
            [['parent_id', 'user_id', 'type'], 'required'],
            [['parent_id', 'user_id'], 'integer'],
            [['type'], 'string'],
            [['created_at'], 'safe'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent ID',
            'user_id' => 'User ID',
            'type' => 'Type',
            'created_at' => 'Created At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function beforeSave($insert) {
        if ($this->isNewRecord) {
            $this->created_at = date('y-m-d H-i-s');
        }

        return parent::beforeSave($insert);
    }
}
