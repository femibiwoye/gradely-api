<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "recommendation_topics".
 *
 * @property int $id
 * @property string|null $recommendation_id
 * @property int|null $subject_id
 * @property int|null $obejct_id Id of the practice or video 
 * @property string|null $object_type This is to let us know if the action is practice or video
 * @property string|null $created_at
 */
class RecommendationTopics extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'recommendation_topics';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subject_id'], 'required'],
            [['id', 'subject_id', 'object_id'], 'integer'],
            [['object_type'], 'string'],
            [['recommendation_id', 'created_at'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'recommendation_id' => 'Recommendation ID',
            'subject_id' => 'Subject ID',
            'object_id' => 'Object ID',
            'object_type' => 'Object Type',
            'created_at' => 'Created At',
        ];
    }
}
