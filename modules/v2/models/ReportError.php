<?php

namespace app\modules\v2\models;

use Yii;

class ReportError extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'report_error';
    }

    public function rules()
    {
        return [
            [['user_id', 'reference_id'], 'required'],
            [['user_id', 'reference_id', 'status'], 'integer'],
            [['description', 'type'], 'string'],
            ['type', 'default', 'value' => 'question'],
            [['created_at'], 'safe'],
            [['school', 'class', 'title'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'school' => 'School',
            'class' => 'Class',
            'reference_id' => 'Reference ID',
            'title' => 'Title',
            'description' => 'Description',
            'type' => 'Type',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
