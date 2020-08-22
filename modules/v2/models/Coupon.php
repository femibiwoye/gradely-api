<?php

namespace app\modules\v2\models;

use Yii;

class Coupon extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'coupon';
    }

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
