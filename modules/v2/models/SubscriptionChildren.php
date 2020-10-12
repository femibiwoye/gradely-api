<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "subscription_children".
 *
 * @property int $id
 * @property int $subscription_id
 * @property int $subscriber_id
 * @property int $student_id
 * @property string $payment_status
 * @property string|null $expiry
 * @property string $created_at
 *
 * @property Subscriptions $subscription
 */
class SubscriptionChildren extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subscription_children';
    }

    /**
     * {@inheritdoc}
     */
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

    public function fields()
    {
        $fields = parent::fields();

        $fields['childName'] = 'childName';

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * Gets query for [[Subscription]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubscription()
    {
        return $this->hasOne(Subscriptions::className(), ['id' => 'subscription_id']);
    }

    /**
     * Gets query for [[Student]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudent()
    {
        return $this->hasOne(User::className(), ['id' => 'student_id']);
    }

    public function getChildName()
    {
        $student = $this->student;
        return isset($student) ? $student->firstname . ' ' . $student->lastname : null;
    }
}
