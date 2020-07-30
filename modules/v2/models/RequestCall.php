<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "request_call".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $subject
 * @property string|null $title
 * @property string|null $description
 * @property string|null $created_at
 *
 * @property User $user
 */
class RequestCall extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request_call';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['description'], 'string'],
            [['created_at'], 'safe'],
            [['email', 'title'], 'string', 'max' => 50],
            [['phone'], 'string', 'max' => 15],
            [['subject'], 'string', 'max' => 200],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['title','description','phone'],'required','on'=>'new-call']
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
            'email' => 'Email',
            'phone' => 'Phone',
            'subject' => 'Subject',
            'title' => 'Title',
            'description' => 'Description',
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
}
