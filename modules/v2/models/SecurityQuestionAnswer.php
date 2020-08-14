<?php

namespace app\modules\v2\models;

use Yii;

class SecurityQuestionAnswer extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'security_question_answer';
    }

    public function rules()
    {
        return [
            [['user_id', 'question', 'answer'], 'required'],
            [['user_id', 'question'], 'integer'],
            [['created_at'], 'safe'],
            [['answer'], 'string', 'max' => 100],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['question'], 'exist', 'skipOnError' => true, 'targetClass' => SecurityQuestions::className(), 'targetAttribute' => ['question' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'question' => 'Question',
            'answer' => 'Answer',
            'created_at' => 'Created At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getQuestion0()
    {
        return $this->hasOne(SecurityQuestions::className(), ['id' => 'question']);
    }
}
