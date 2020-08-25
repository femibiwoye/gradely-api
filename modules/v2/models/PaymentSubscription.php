<?php

namespace app\modules\v2\models;

use Yii;
use yii\base\Model;
use app\modules\v2\models\{Coupon, PaymentPlan, Subscriptions, SubscriptionChildren};
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class PaymentSubscription extends Model
{
    public $payment_plan_id;
    public $quantity;
    public $children;
    public $subscription_status;
    public $student_id;
    public $coupon;
    public $user_id;

    public function rules()
    {
        return [
            [['payment_plan_id', 'quantity', 'children', 'subscription_status'], 'required'],
            [['payment_plan_id', 'quantity', 'student_id'], 'integer'],
            [['subscription_status'], 'string'],
            [['coupon'], 'exist', 'targetClass' => Coupon::className(), 'targetAttribute' => ['coupon' => 'coupon']],
            ['children', 'each', 'rule' => ['integer']],
            ['payment_plan_id', 'exist', 'targetClass' => PaymentPlan::className(), 'targetAttribute' => ['payment_plan_id' => 'id']],
            ['student_id', 'validateUser'],
        ];
    }

    public function validateUser()
    {
        if (Yii::$app->user->identity->type == SharedConstant::ACCOUNT_TYPE[2] && empty($this->student_id)) {
            return $this->addError('Student ID is requried');
        }
    }

    public function addPaymentSubscription()
    {
        $model = new Subscriptions;
        $model->user_id = $this->student_id ? $this->student_id : $this->user_id;
        $model->duration = SharedConstant::SUBSCRIPTION_DURATION;
        $model->price = ($this->paymentPlan->price * count($this->children));
        $model->duration_count = $this->paymentPlan->months_duration;
        $model->plan = SharedConstant::SUBSCRIPTION_PLAN;
        $model->quantity = count($this->children);
        $model->total = $this->coupon ? (($this->paymentPlan->price * count($this->children)) - $this->coupon->percentage) : $this->paymentPlan->price * count($this->children);
        $model->payment = $this->subscription_status;
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {

            if (!$model->save()) {
                return false;
            }

            if (!$this->addSubscriptionChildren($model, $this->user_id, $this->student_id)) {
                return false;
            }

            $dbtransaction->commit();
        } catch (Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }

        return $model;
    }

    public function getPaymentPlan()
    {
        return PaymentPlan::findOne(['id' => $this->payment_plan_id]);
    }

    public function getCoupon()
    {
        return Coupon::findOne(['id' => $this->coupon]);
    }

    public function addSubscriptionChildren($subscription, $subscriber_id, $student_id)
    {
        $model = new SubscriptionChildren;
        $model->subscription_id = $subscription->id;
        $model->subscriber_id = $subscriber_id;
        $model->student_id = $student_id ? $student_id : $subscriber_id;
        $model->payment_status = $subscription->payment;
        if (!$model->save()) {
            return false;
        }

        return true;
    }

}