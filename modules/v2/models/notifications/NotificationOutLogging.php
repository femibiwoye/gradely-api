<?php

namespace app\modules\v2\models\notifications;

use Yii;

/**
 * This is the model class for table "notification_out_logging".
 *
 * @property int $id
 * @property int $action_id
 * @property int $notification_id
 * @property int $receiver_id
 * @property string $notification_type
 * @property int $status
 * @property string $created_at
 */
class NotificationOutLogging extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notification_out_logging';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['action_id', 'notification_id', 'receiver_id', 'notification_type'], 'required'],
            [['action_id', 'notification_id', 'receiver_id', 'status'], 'integer'],
            [['notification_type'], 'string'],
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
            'action_id' => 'Action ID',
            'notification_id' => 'Notification ID',
            'receiver_id' => 'Receiver ID',
            'notification_type' => 'Notification Type',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->get('notification');
    }
}
