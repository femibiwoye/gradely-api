<?php

namespace app\modules\v2\models\handler;

use Yii;

/**
 * This is the model class for table "session_logger".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $type
 * @property string|null $url
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class SessionLogger extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'session_logger';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['url'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['type'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'type' => 'Type',
            'url' => 'Url',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
    public static function getDb()
    {
        return Yii::$app->handler;
    }
}
