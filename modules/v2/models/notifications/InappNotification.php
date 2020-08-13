<?php

namespace app\modules\v2\models\notifications;

use Yii;

/**
 * This is the model class for table "inapp_notification".
 *
 * @property int $id
 * @property int $user_id
 * @property int $notification_id
 * @property int $out_logging_id
 * @property string $message The final message to be seen by user
 * @property int|null $read_status 0 means not read and 1 means read
 * @property string $created_at
 */
class InappNotification extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'inapp_notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'notification_id', 'out_logging_id', 'message'], 'required'],
            [['user_id', 'notification_id', 'out_logging_id', 'read_status'], 'integer'],
            [['message'], 'string'],
            [['created_at'], 'safe'],
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
            'notification_id' => 'Notification ID',
            'out_logging_id' => 'Out Logging ID',
            'message' => 'Message',
            'read_status' => 'Read Status',
            'created_at' => 'Created At',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->get('notification');
    }
}
