<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "invite_log".
 *
 * @property int $id
 * @property string $receiver_email
 * @property string|null $receiver_name
 * @property string $receiver_type
 * @property string|null $receiver_phone
 * @property int|null $receiver_class
 * @property int|null $receiver_subject
 * @property string $sender_type
 * @property int $sender_id
 * @property string $token
 * @property string|null $extra_data
 * @property int $status
 * @property string $created_at
 */
class InviteLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invite_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['receiver_email', 'receiver_type', 'sender_type', 'sender_id', 'token'], 'required'],
            [['receiver_class', 'receiver_subject', 'sender_id', 'status'], 'integer'],
            [['extra_data'], 'string'],
            [['created_at'], 'safe'],
            [['receiver_email', 'receiver_name'], 'string', 'max' => 100],
            [['receiver_type', 'sender_type'], 'string', 'max' => 20],
            [['receiver_phone'], 'string', 'max' => 50],
            [['token'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'receiver_email' => 'Receiver Email',
            'receiver_name' => 'Receiver Name',
            'receiver_type' => 'Receiver Type',
            'receiver_phone' => 'Receiver Phone',
            'receiver_class' => 'Receiver Class',
            'receiver_subject' => 'Receiver Subject',
            'sender_type' => 'Sender Type',
            'sender_id' => 'Sender ID',
            'token' => 'Token',
            'extra_data' => 'Extra Data',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
