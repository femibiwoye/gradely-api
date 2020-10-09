<?php

namespace app\modules\v2\models;

use Yii;

class PaymentPlan extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'payment_plan';
    }

    public function rules()
    {
        return [
            [['type'], 'required'],
            [['type'], 'string'],
            [['price'], 'number'],
            [['months_duration', 'status'], 'integer'],
            [['created_at'], 'safe'],
            [['slug', 'title'], 'string', 'max' => 50],
            [['sub_title'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 200],
            [['curriculum'], 'string', 'max' => 45],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'slug' => 'Slug',
            'title' => 'Title',
            'price' => 'Price',
            'sub_title' => 'Sub Title',
            'description' => 'Description',
            'curriculum' => 'Curriculum',
            'months_duration' => 'Months Duration',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
