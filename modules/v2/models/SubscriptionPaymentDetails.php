<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "subscription_payment_details".
 *
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string|null $customer_code
 * @property int $selected
 * @property string $payment_channel
 * @property string $authorization_code
 * @property string $bin
 * @property string $last4
 * @property string $exp_month
 * @property string $exp_year
 * @property string $channel
 * @property string $card_type
 * @property string $bank
 * @property string $country_code
 * @property string $brand
 * @property int $reusable
 * @property string $signature
 * @property string $created_at
 */
class SubscriptionPaymentDetails extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscription_payment_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'email', 'authorization_code', 'bin', 'last4', 'exp_month', 'exp_year', 'channel', 'card_type', 'bank', 'country_code', 'brand', 'reusable', 'signature'], 'required'],
            [['user_id', 'selected', 'reusable'], 'integer'],
            [['created_at'], 'safe'],
            [['email', 'customer_code', 'authorization_code', 'signature'], 'string', 'max' => 255],
            [['payment_channel', 'bin', 'last4', 'exp_month', 'exp_year', 'channel', 'card_type', 'bank', 'country_code', 'brand'], 'string', 'max' => 100],
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
            'email' => 'Email',
            'customer_code' => 'Customer Code',
            'selected' => 'Selected',
            'payment_channel' => 'Payment Channel',
            'authorization_code' => 'Authorization Code',
            'bin' => 'Bin',
            'last4' => 'Last4',
            'exp_month' => 'Exp Month',
            'exp_year' => 'Exp Year',
            'channel' => 'Channel',
            'card_type' => 'Card Type',
            'bank' => 'Bank',
            'country_code' => 'Country Code',
            'brand' => 'Brand',
            'reusable' => 'Reusable',
            'signature' => 'Signature',
            'created_at' => 'Created At',
        ];
    }

    public function savePaymentDetails($authorization, $customer)
    {
        $existingModel = SubscriptionPaymentDetails::find()->where(['user_id' => Yii::$app->user->id, 'last4' => $authorization->last4, 'authorization_code' => $authorization->authorization_code]);
        if ($existingModel->exists()) {
            return $existingModel->one();
        }
        $model = new SubscriptionPaymentDetails();
        $model->user_id = Yii::$app->user->id;
        //Customer details
        $model->email = $customer->email;
        $model->customer_code = $customer->customer_code;

        //Authorization details
        $model->authorization_code = $authorization->authorization_code;
        $model->bin = $authorization->bin;
        $model->last4 = $authorization->last4;
        $model->exp_month = $authorization->exp_month;
        $model->exp_year = $authorization->exp_year;
        $model->channel = $authorization->channel;
        $model->card_type = $authorization->card_type;
        $model->bank = $authorization->bank;
        $model->country_code = $authorization->country_code;
        $model->brand = $authorization->brand;
        $model->reusable = $authorization->reusable;
        $model->signature = $authorization->signature;
        $model->reusable = $authorization->reusable ? 1 : 0;

        $model->selected = 1;
        $model->save();
        return $model;
    }
}
