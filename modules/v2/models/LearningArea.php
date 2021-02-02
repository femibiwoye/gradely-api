<?php

namespace app\modules\v2\models;

use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "learning_area".
 *
 * @property int $id
 * @property int $class_id
 * @property int $subject_id
 * @property int $topic_id
 * @property string $topic
 * @property string $slug
 * @property string|null $description
 * @property int $week
 * @property int $is_school
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property SubjectTopics $topic0
 */
class LearningArea extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'learning_area';
    }

    public function behaviors()
    {
        return [
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'topic',
                'ensureUnique' => true
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['class_id', 'subject_id', 'topic_id', 'topic', 'slug','week'], 'required'],
            [['class_id', 'subject_id', 'topic_id'], 'integer'],
            [['description'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['topic', 'slug'], 'string', 'max' => 100],
            [['topic_id'], 'exist', 'skipOnError' => true, 'targetClass' => SubjectTopics::className(), 'targetAttribute' => ['topic_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'class_id' => 'Class ID',
            'subject_id' => 'Subject ID',
            'topic_id' => 'Topic ID',
            'topic' => 'Topic',
            'slug' => 'Slug',
            'description' => 'Description',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Topic0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTopic0()
    {
        return $this->hasOne(SubjectTopics::className(), ['id' => 'topic_id']);
    }
}
