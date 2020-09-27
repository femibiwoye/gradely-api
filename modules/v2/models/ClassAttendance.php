<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "class_attendance".
 *
 * @property int $id
 * @property int $session_id
 * @property int $user_id id of user attending a meeting
 * @property string|null $type
 * @property string $joined_at
 * @property string|null $joined_updated
 * @property string|null $ended_at
 * @property string $token Generated for student joining the class
 *
 * @property TutorSession $session
 * @property User $user
 */
class ClassAttendance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'class_attendance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['session_id', 'user_id', 'token'], 'required'],
            [['session_id', 'user_id'], 'integer'],
            [['type', 'token'], 'string'],
            [['joined_at', 'joined_updated', 'ended_at'], 'safe'],
            [['session_id'], 'exist', 'skipOnError' => true, 'targetClass' => TutorSession::className(), 'targetAttribute' => ['session_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'session_id' => 'Session ID',
            'user_id' => 'User ID',
            'type' => 'Type',
            'joined_at' => 'Joined At',
            'joined_updated' => 'Joined Updated',
            'ended_at' => 'Ended At',
            'token' => 'Token',
        ];
    }

    /**
     * Gets query for [[Session]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSession()
    {
        return $this->hasOne(TutorSession::className(), ['id' => 'session_id']);
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
