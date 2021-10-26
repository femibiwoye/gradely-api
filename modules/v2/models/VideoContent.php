<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "video_content".
 *
 * @property int $id
 * @property string $category
 * @property int $subject_id
 * @property int $topic_id
 * @property int|null $content_id
 * @property string $title
 * @property string $new_title
 * @property string $slug
 * @property string|null $image
 * @property string|null $token
 * @property int|null $content_length
 * @property string $content_type
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string $created_at
 * @property string|null $owner
 * @property string|null $path
 * @property string|null $updated_at
 *
 * @property VideoAssign[] $videoAssigns
 */
class VideoContent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'video_content';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category', 'subject_id', 'topic_id', 'title', 'new_title', 'slug','token'], 'required'],
            [['subject_id', 'topic_id', 'content_id', 'content_length', 'created_by', 'updated_by'], 'integer'],
            [['image', 'owner','path'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['category','token'], 'string', 'max' => 100],
            [['title', 'slug'], 'string', 'max' => 255],
            [['content_type'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category' => 'Category',
            'subject_id' => 'Subject ID',
            'topic_id' => 'Topic ID',
            'content_id' => 'Content ID',
            'title' => 'Title',
            'slug' => 'Slug',
            'image' => 'Image',
            'content_length' => 'Content Length',
            'content_type' => 'Content Type',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        //if ($this->isRelationPopulated('views')) {
        $fields['creator'] = 'creator';
        $fields['like'] = 'like';
        $fields['dislike'] = 'dislike';
        $fields['views'] = 'views';
        $fields['my_status'] = 'myStatus';
        //}


        return $fields;
    }

    /**
     * Gets query for [[VideoAssigns]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVideoAssigns()
    {
        return $this->hasMany(VideoAssign::className(), ['content_id' => 'id']);
    }

    public function getVideoAssigned()
    {
        return $this->hasOne(VideoAssign::className(), ['content_id' => 'id']);
    }

    public function getViews()
    {
        return $this->hasMany(FileLog::className(), ['file_id' => 'id'])->andWhere(['type' => 'video'])->count();
    }

    public function getLike()
    {
        return $this->hasMany(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => 'video', 'status' => 1])->count();
    }

    public function getDislike()
    {
        return $this->hasMany(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => 'video', 'status' => 0])->count();
    }

    public function getCreator()
    {
        return null;
    }

    public function getMyStatus()
    {
        $model = FeedLike::find()->where(['parent_id' => $this->id, 'type' => 'video', 'user_id' => Yii::$app->user->id])->one();

        if ($model)
            return $model->status;
        return null;
    }

}
