<?php

namespace app\modules\v2\models;

use Yii;

class Comprehension extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'comprehension';
    }

    public function rules()
    {
        return [
            [['title', 'body', 'created_by'], 'required'],
            [['body'], 'string'],
            [['status', 'created_by', 'updated_by'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 200],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'body' => 'Body',
            'status' => 'Status',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getQuestions()
    {
        return $this->hasMany(Questions::className(), ['comprehension_id' => 'id']);
    }

    public static function getDb()
    {
        return Yii::$app->get('dblive');
    }
}
