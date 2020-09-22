<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "recommended_resources".
 *
 * @property int $id
 * @property int $creator_id The person creating or recommending the resources. E.g teacher_id
 * @property int $receiver_id This is id of the student receiving the resources.
 * @property string $resources_type e.g video, document, practice, etc
 * @property int $resources_id Id of the resources recommended to the student
 * @property string|null $reference_type e.g homework, practice, class, etc.
 * @property int|null $reference_id The is action that led to the recommendation of the resources. e.g homework_id, class_id, etc
 * @property string|null $created_at
 */
class RecommendedResources extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'recommended_resources';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['creator_id', 'receiver_id', 'resources_type', 'resources_id', 'reference_type','reference_id'], 'required'],
            [['creator_id', 'receiver_id', 'resources_id', 'reference_id'], 'integer'],
            [['reference_type'], 'in', 'range' => ['class' , 'homework']],
            [['created_at'], 'safe'],
            [['resources_type', 'reference_type'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'creator_id' => 'Creator ID',
            'receiver_id' => 'Receiver ID',
            'resources_type' => 'Resources Type',
            'resources_id' => 'Resources ID',
            'reference_type' => 'Reference Type',
            'reference_id' => 'Reference ID',
            'created_at' => 'Created At',
        ];
    }
}
