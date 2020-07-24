<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

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
            [['type', 'class_id', 'description', 'view_by'], 'required'],
            ['view_by', 'teacherViewBy'],
            [['user_id', 'reference_id', 'likes', 'class_id', 'global_class_id', 'status'], 'integer'],
            [['description', 'type', 'view_by'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['subject_id'], 'string', 'max' => 45],
            [['token'], 'string', 'max' => 100],
            [['token'], 'unique'],
            [['class_id'], 'exist', 'skipOnError' => true, 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
            [['global_class_id'], 'exist', 'skipOnError' => true, 'targetClass' => GlobalClass::className(), 'targetAttribute' => ['global_class_id' => 'id']],
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
            'global_class_id' => 'Global Class ID',
            'view_by' => 'View By',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'description',
            'type',
            'likesCount' => 'feedLikeCount',
            'commentCount' => 'feedCommentCount',
            'token',
            'view_by',
            'myLike',
            'created_at',
            'updated_at',
            'user',
            'reference',
            'subject',
            'class',
            'global_class_id' => 'globalClass',
            'comment' => 'miniComment',
        ];
    }

    public function teacherViewBy($value)
    {
        if (!in_array($this->view_by, SharedConstant::TEACHER_VIEW_BY)) {
            $this->addError('view_by', "'$this->view_by' is not a valid option");
            return false;
        }
        return true;
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getReference()
    {
        if ($this->type == SharedConstant::FEED_TYPES[2]) {
            return $this->hasOne(Homeworks::className(), ['id' => 'reference_id']);
        }

        if ($this->type == SharedConstant::FEED_TYPES[3]) {
            return $this->hasOne(TutorSession::className(), ['id' => 'reference_id']);
        }

        if ($this->type == SharedConstant::FEED_TYPES[1]) {
            return null;
        }

        if ($this->type == SharedConstant::FEED_TYPES[4] || $this->type == SharedConstant::FEED_TYPES[5]) {
            return $this->hasOne(PracticeMaterial::className(), ['id' => 'reference_id']);
        }
    }

    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }

    public function getClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id']);
    }

    public function getGlobalClass()
    {
        return GlobalClass::findOne(['id' => $this->global_class_id]);
    }

    public function getFeedLike()
    {
        return $this->hasOne(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => SharedConstant::FEED_TYPE]);
    }

    public function getMyLike()
    {
        return $this->hasOne(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => SharedConstant::FEED_TYPE, 'user_id' => Yii::$app->user->id])->exists() ? 1 : 0;
    }

    public function getFeedLikeCount()
    {
        return $this->hasOne(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => SharedConstant::FEED_TYPE])->count();
    }

    public function getFeedCommentCount()
    {
        return $this->hasMany(FeedComment::className(), ['feed_id' => 'id'])->count();
    }

    public function getMiniComment()
    {
        return $this->hasMany(FeedComment::className(), ['feed_id' => 'id'])->limit(2)->orderBy('id DESC');
    }

    public function FeedDisliked()
    {
        $model = $this->getFeedLike()->andWhere(['user_id' => Yii::$app->user->id])->one();
        if (!$model->delete()) {
            return false;
        }

        return true;
    }

    public function beforeSave($insert)
    {
        $this->global_class_id = Classes::findOne(['id' => $this->class_id])->global_class_id;
        if ($this->isNewRecord) {
            $this->token = GenerateString::widget();
            $this->created_at = date('y-m-d H-i-s');
            //$this->updated_at = date('y-m-d H-i-s');
        } else {
            $this->updated_at = date('y-m-d H-i-s');
        }
        return parent::beforeSave($insert);
    }
}
