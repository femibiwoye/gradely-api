<?php

namespace app\modules\v2\models;

use Yii;

class SubscriptionChildren extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'subscription_children';
    }

    public function rules()
    {
        return [
            [['subscription_id', 'subscriber_id', 'student_id'], 'required'],
            [['subscription_id', 'subscriber_id', 'student_id'], 'integer'],
            [['payment_status'], 'string'],
            [['expiry', 'created_at'], 'safe'],
            [['subscription_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscriptions::className(), 'targetAttribute' => ['subscription_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'subscription_id' => 'Subscription ID',
            'subscriber_id' => 'Subscriber ID',
            'student_id' => 'Student ID',
            'payment_status' => 'Payment Status',
            'expiry' => 'Expiry',
            'created_at' => 'Created At',
        ];
    }

    public function getSubscription()
    {
        return $this->hasOne(Subscriptions::className(), ['id' => 'subscription_id']);
    }
}
