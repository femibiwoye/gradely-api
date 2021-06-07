<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "user_tour".
 *
 * @property int $id
 * @property string $name
 * @property int $user_id
 * @property string $type
 * @property string|null $created_at
 */
class FeatureUserLogger extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feature_user_logger';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'user_id', 'type'], 'required'],
            [['user_id'], 'integer'],
            [['type'], 'string'],
            [['created_at'], 'safe'],
            [['name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'user_id' => 'User ID',
            'type' => 'Type',
            'created_at' => 'Created At',
        ];
    }
}
