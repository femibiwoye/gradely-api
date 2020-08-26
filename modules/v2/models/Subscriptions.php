<?php

namespace app\modules\v2\models;

use app\paystack\Paystack;
use Yii;

/**
 * This is the model class for table "subscriptions".
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $payment_details_id ID of the card details used
 * @property int|null $reference_id e.g tutor_id or any id to reference this payment
 * @property float $price Price to be paid
 * @property int $quantity number of children you pay for
 * @property string|null $duration month, year or pays(e.g tutor session)
 * @property int $duration_count This could be number of month/year you paying for subscription, it also means number of session you paying for if you using payg(like tutors session)
 * @property float $total This is total amount to be paid after adding all children and all applying coupon
 * @property string $payment Paid or unpaid
 * @property float|null $amount_paid Actual paid amount after all calculation
 * @property string|null $transaction_id Unique id for this payment
 * @property string|null $payment_plan_id This is ID of the payment from payment_plan table
 * @property string|null $plan_code Incase you are using gateway automatic subscription
 * @property string|null $plan Basic & Premium is access to regular paid service.
 * payg is a one time payment for a service, e.g you paid for tutor want.
 * subscription is continuous interval payment for a service, e.g every week, month, etc tutor service.
 * @property string $type Subscription is for regular monthly subscription while tutor is a PAYG service for tutor session.
 * @property string|null $meta You can put any additional data here
 * @property int|null $renew_status Automatically renew my subscription at the end of the current subscription cycle
 * @property string|null $coupon If coupon provided
 * @property int $status 1 means subscription is active and it is default, 0 means subscription has been disabled.
 * @property string $created_at
 * @property string|null $paid_at
 *
 * @property SubscriptionChildren[] $subscriptionChildrens
 */
class Subscriptions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscriptions';
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * Gets query for [[SubscriptionChildrens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubscriptionChildrens()
    {
        return $this->hasMany(SubscriptionChildren::className(), ['subscription_id' => 'id']);
    }

    public function ConfirmPayment(Subscriptions $model)
    {

        if ($model->payment == 'paid') {
            return ['message' => 'Already paid', 'model' => $model];
        }
        $paystack = new Paystack(Yii::$app->params['payment_sk']);
        $responseObj = $paystack->transaction->verify(["reference" => $model->transaction_id]);

        if ($responseObj->data->status == 'success') {
            if ($model->payment == 'unpaid') {
                $model->payment = 'paid';
                $model->paid_at = date('Y-m-d h:i:s');
                $model->amount_paid = $responseObj->data->amount / 100;

                //This saves the card details provided by the payment merchant
                $paymentDetails = new SubscriptionPaymentDetails();
                $modelPaymentDetails = $paymentDetails->savePaymentDetails($responseObj->data->authorization, $responseObj->data->customer);

                //This link the id of the payment details to subscription.
                $model->payment_details_id = $modelPaymentDetails->id;
                if ($model->save() && $model->plan != 'payg' && $model->plan != 'subscription') {
                    $this->activateChildSubscription($model);
                }

                //Payment successful
                return ['message' => 'Payment successful', 'model' => $model];
            }

            // Payment already made
            return ['message' => 'Already paid', 'model' => $model];
        }
        return ['message' => 'Payment not successful', 'model' => $model];

    }

    public function activateChildSubscription($model)
    {
        $children = SubscriptionChildren::find()->where(['subscription_id' => $model->id, 'subscriber_id' => Yii::$app->user->id])->all();
        $expiry = date('Y-m-d H:i:s', strtotime("+{$model->duration_count} {$model->duration}"));

        foreach ($children as $item) {
            $item->payment_status = 'paid';
            $item->expiry = $expiry;
            $item->save();

            $child = UserModel::find()->where(['id' => $item->student_id, 'type' => 'student'])->one();
            $model->scenario = 'update-subscription';
            $child->subscription_expiry = $expiry;
            $child->subscription_plan = $model->plan;
            $child->save();
        }
    }
}
