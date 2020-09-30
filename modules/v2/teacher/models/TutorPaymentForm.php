<?php

namespace app\modules\v2\teacher\models;

use Yii;
use yii\base\Model;
use app\modules\v2\models\{Coupon, PaymentPlan, Subscriptions, SubscriptionChildren, TutorSession, TutorSessionParticipant, TutorSessionTiming, User, GenerateString};
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class TutorPaymentForm extends Model
{
    public $payment_plan_id;
    public $children;
    public $coupon;
    public $user_id;
    public $reference_id;
    public $schedule;
    public $type;
    public $duration;


    public function rules()
    {
        return [
            [['payment_plan_id', 'children', 'user_id', 'reference_id', 'schedule', 'type', 'duration'], 'required'],
            [['coupon'], 'exist', 'targetClass' => Coupon::className(), 'targetAttribute' => ['coupon' => 'code']],
            ['children', 'each', 'rule' => ['integer']],
            ['payment_plan_id', 'exist', 'targetClass' => PaymentPlan::className(), 'targetAttribute' => ['payment_plan_id' => 'id']],
            ['reference_id', 'exist', 'targetClass' => User::className(), 'targetAttribute' => ['reference_id' => 'id']],
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
        $model->duration = $this->duration;
        $model->price = ($this->paymentPlan->price * count($this->children));
        $model->duration_count = $this->durationCount();
        $model->plan = SharedConstant::PAY_AS_YOU_GO;
        $model->quantity = count($this->children);
        $model->payment_plan_id = $this->payment_plan_id;
        if (!empty($this->coupon))
            $model->coupon = $this->coupon;

        $model->transaction_id = GenerateString::widget(['length' => 20]) . mt_rand(10, 99);
        $model->payment = SharedConstant::UN_PAID;
        $model->total = !empty($this->coupon) ? ($model->price - (($model->price * $this->couponDetails->percentage) / 100)) : $model->price;
        $model->reference_id = $this->reference_id;
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()) {
                return false;
            }

            if (!$this->addSubscriptionChildren($model, $this->user_id, $this->children)) {
                return false;
            }

            if (!$this->addTutorSession()) {
                return false;
            }

            $dbtransaction->commit();
            return $model;
        } catch (Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }
    }

    private function durationCount()
    {
        if ($this->type == 'daily') {
            return SharedConstant::VALUE_ONE;
        } elseif ($this->type == 'weekly') {
            return SharedConstant::VALUE_THREE;
        } else {
            return SharedConstant::VALUE_TWELVE;
        }
    }

    public function getPaymentPlan()
    {
        return PaymentPlan::findOne(['id' => $this->payment_plan_id]);
    }

    public function getCouponDetails()
    {
        return Coupon::findOne(['code' => $this->coupon]);
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

    public function addTutorSession()
    {
        $model = new TutorSession;
        $model->requester_id = $this->user_id;
        $model->category = 'class';
        if (count($this->children) == SharedConstant::VALUE_ONE) {
            $model->student_id = $this->children[SharedConstant::VALUE_ZERO];
            $model->participant_type = 'single';
        } else {
            $model->participant_type = 'multiple';
        }

        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()) {
                return false;
            }

            if ($this->type == 'multiple' && !$this->addTutorSessionParticipants($model)) {
                return false;
            }

            if (!$this->addTutorSessionTimings($model)) {
                return false;
            }

            $dbtransaction->commit();
            return $model;
        } catch (Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }
    }

    public function addTutorSessionParticipants($tutor_session)
    {
        foreach ($this->children as $children) {
            $model = new TutorSessionParticipant;
            $model->session_id = $tutor_session->id;
            $model->participant_id = $children;
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }

    public function addTutorSessionTimings($tutor_session)
    {
        foreach ($this->schedule as $schedule) {
            $model = new TutorSessionTiming;
            $model->session_id = $tutor_session->id;
            $model->day = $schedule['day'];
            $model->time = $schedule['time'];
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }

}