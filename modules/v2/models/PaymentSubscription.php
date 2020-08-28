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
    public $children;
    public $coupon;
    public $user_id;

    public function rules()
    {
        return [
            [['payment_plan_id'], 'required', 'on' => 'student-subscription'],
            [['payment_plan_id', 'children'], 'required', 'on' => 'parent-subscription'],
            [['payment_plan_id'], 'integer'],
            [['coupon'], 'exist', 'targetClass' => Coupon::className(), 'targetAttribute' => ['coupon' => 'coupon']],
            ['children', 'each', 'rule' => ['integer']],
            ['payment_plan_id', 'exist', 'targetClass' => PaymentPlan::className(), 'targetAttribute' => ['payment_plan_id' => 'id']],
            ['children', 'validateUser'],
        ];
    }

    public function validateUser()
    {
        if (empty($this->children)) {
            return $this->addError('children', 'Student ID is required');
        }
        if (!in_array(Yii::$app->user->identity->type, [SharedConstant::ACCOUNT_TYPE[2], SharedConstant::ACCOUNT_TYPE[3]])) {
            return $this->addError('children', 'You are not a valid user');
        }
        if (!is_array($this->children)) {
            return $this->addError('children', 'Children must be an array');
        }

        if (Yii::$app->user->identity->type == 'parent' && !Parents::find()->where(['parent_id' => Yii::$app->user->id, 'student_id' => $this->children])->exists()) {
            return $this->addError('children', 'Child id is invalid');
        }


    }

    public function addPaymentSubscription()
    {
        $model = new Subscriptions();
        $model->user_id = Yii::$app->user->id;
        $model->duration = SharedConstant::SUBSCRIPTION_DURATION;
        $model->price = ($this->paymentPlan->price * count($this->children));
        $model->duration_count = $this->paymentPlan->months_duration;
        $model->plan = SharedConstant::SUBSCRIPTION_PLAN;
        $model->quantity = count($this->children);
        $model->payment_plan_id = $this->payment_plan_id;
        $model->transaction_id = GenerateString::widget(['length' => 20]).mt_rand(10,99);
        $model->payment = 'unpaid';
        $model->total = $this->coupon ? ($model->price - (($model->price * $this->coupon->percentage) / 100)) : $model->price;
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()) {
                return false;
            }

            if (!$this->addSubscriptionChildren($model, $this->user_id, $this->children)) {
                return false;
            }

            $dbtransaction->commit();
            return $model;
        } catch (Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }
    }

    public function getPaymentPlan()
    {
        return PaymentPlan::findOne(['id' => $this->payment_plan_id]);
    }

    public function getCoupon()
    {
        return Coupon::findOne(['id' => $this->coupon]);
    }

    public function addSubscriptionChildren($subscription, $subscriber_id, $students)
    {
        foreach ($students as $student) {
            $model = new SubscriptionChildren;
            $model->subscription_id = $subscription->id;
            $model->subscriber_id = $subscriber_id;
            $model->student_id = $student;
            $model->payment_status = $subscription->payment;
            if (!$model->save()) {
                return false;
            }
        }
        return true;
    }

}