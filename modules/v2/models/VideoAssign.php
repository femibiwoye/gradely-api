<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "video_assign".
 *
 * @property int $id
 * @property int $content_id
 * @property int $topic_id Gradely topic id
 * @property string $difficulty
 * @property int $status this enable or disable
 * @property int $created_by
 * @property int|null $updated_by
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property SubjectTopics $topic
 * @property VideoContent $content
 */
class VideoAssign extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'video_assign';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['content_id', 'topic_id', 'difficulty', 'created_by'], 'required'],
            [['content_id', 'topic_id', 'status', 'created_by', 'updated_by'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['difficulty'], 'string', 'max' => 100],
            [['topic_id'], 'exist', 'skipOnError' => true, 'targetClass' => SubjectTopics::className(), 'targetAttribute' => ['topic_id' => 'id']],
            [['content_id'], 'exist', 'skipOnError' => true, 'targetClass' => VideoContent::className(), 'targetAttribute' => ['content_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'content_id' => 'Content ID',
            'topic_id' => 'Topic ID',
            'difficulty' => 'Difficulty',
            'status' => 'Status',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Topic]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTopic()
    {
        return $this->hasOne(SubjectTopics::className(), ['id' => 'topic_id']);
    }

    /**
     * Gets query for [[Content]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContent()
    {
        return $this->hasOne(VideoContent::className(), ['id' => 'content_id']);
    }
}
