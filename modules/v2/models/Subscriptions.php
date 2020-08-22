<?php

namespace app\modules\v2\models;

use Yii;

class Subscriptions extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'subscriptions';
    }

    public function rules()
    {
        return [
            [['user_id', 'price', 'quantity', 'duration_count', 'total'], 'required'],
            [['user_id', 'payment_details_id', 'reference_id', 'quantity', 'duration_count', 'renew_status', 'status'], 'integer'],
            [['price', 'total', 'amount_paid'], 'number'],
            [['duration', 'payment', 'plan', 'type', 'meta'], 'string'],
            [['created_at', 'paid_at'], 'safe'],
            [['transaction_id', 'coupon'], 'string', 'max' => 50],
            [['plan_code'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'payment_details_id' => 'Payment Details ID',
            'reference_id' => 'Reference ID',
            'price' => 'Price',
            'quantity' => 'Quantity',
            'duration' => 'Duration',
            'duration_count' => 'Duration Count',
            'total' => 'Total',
            'payment' => 'Payment',
            'amount_paid' => 'Amount Paid',
            'transaction_id' => 'Transaction ID',
            'plan_code' => 'Plan Code',
            'plan' => 'Plan',
            'type' => 'Type',
            'meta' => 'Meta',
            'renew_status' => 'Renew Status',
            'coupon' => 'Coupon',
            'status' => 'Status',
            'created_at' => 'Created At',
            'paid_at' => 'Paid At',
        ];
    }

    public function getSubscriptionChildrens()
    {
        return $this->hasMany(SubscriptionChildren::className(), ['subscription_id' => 'id']);
    }
}
