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
 * @property string $slug
 * @property string|null $image
 * @property int|null $content_length
 * @property string $content_type
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property string $created_at
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
            [['category', 'subject_id', 'topic_id', 'title', 'slug'], 'required'],
            [['subject_id', 'topic_id', 'content_id', 'content_length', 'created_by', 'updated_by'], 'integer'],
            [['image'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['category'], 'string', 'max' => 100],
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

    /**
     * Gets query for [[VideoAssigns]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVideoAssigns()
    {
        return $this->hasMany(VideoAssign::className(), ['content_id' => 'id']);
    }
}
