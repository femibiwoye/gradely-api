<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "security_question_answer".
 *
 * @property int $id
 * @property int $user_id
 * @property int $question This question ID is dependent on security_questions table
 * @property string $answer
 * @property string $created_at
 *
 * @property User $user
 * @property SecurityQuestions $question0
 */
class SecurityQuestionAnswer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'security_question_answer';
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * Gets query for [[Question0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion0()
    {
        return $this->hasOne(SecurityQuestions::className(), ['id' => 'question']);
    }
}
