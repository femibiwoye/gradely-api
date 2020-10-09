<?php

namespace app\modules\v2\models\notifications;

use Yii;

/**
 * This is the model class for table "notification_messages".
 *
 * @property int $id
 * @property int $action_id from action table
 * @property int $is_parameter 0 means it is a general url and therefore requires no parameter, 1 means it requires a parameter
 * @property string $message This contains the raw message
 * @property string $type This determine if the message is for whatsapp, sms, in-app, etc
 * @property int $status
 * @property string $created_at
 */
class NotificationMessages extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notification_messages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['action_id', 'is_parameter', 'status'], 'integer'],
            [['message', 'type'], 'required'],
            [['message', 'type'], 'string'],
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
            'is_parameter' => 'Is Parameter',
            'message' => 'Message',
            'type' => 'Type',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->get('notification');
    }
}
