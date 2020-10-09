<?php

namespace app\modules\v2\models;

use Yii;

class SecurityQuestions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'security_questions';
    }

    public function rules()
    {
        return [
            [['question'], 'required'],
            [['question'], 'string'],
            [['updated_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'question' => 'Question',
            'updated_at' => 'Updated At',
        ];
    }

    public function getSecurityQuestionAnswers()
    {
        return $this->hasMany(SecurityQuestionAnswer::className(), ['question' => 'id']);
    }
}
