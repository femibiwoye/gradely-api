<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "coupon".
 *
 * @property int $id
 * @property string $code The coupon code
 * @property int $percentage The percentage of the coupon
 * @property string $is_time_bound 0 means this coupon doesn’t have time limit
 * @property string|null $start_time When coupon should be open to use
 * @property string|null $end_time When usage should end
 * @property string $coupon_payment_type All means it can be used for catchup or tutor, and their respective type
 * @property int|null $status 1 means it is valid, 0 means it has been disabled
 * @property string $created_at
 * @property string|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class Coupon extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coupon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'percentage'], 'required'],
            [['percentage', 'status', 'created_by', 'updated_by'], 'integer'],
            [['coupon_payment_type'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['code'], 'string', 'max' => 50],
            [['is_time_bound', 'start_time', 'end_time'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'percentage' => 'Percentage',
            'is_time_bound' => 'Is Time Bound',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'coupon_payment_type' => 'Coupon Payment Type',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
        ];
    }
}
