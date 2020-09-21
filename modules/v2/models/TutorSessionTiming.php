<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "tutor_session_timing".
 *
 * @property int $id
 * @property int $session_id
 * @property string $day
 * @property string $time
 * @property string $created_at
 *
 * @property TutorSession $session
 */
class TutorSessionTiming extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tutor_session_timing';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['session_id', 'day', 'time'], 'required'],
            [['session_id'], 'integer'],
            [['created_at'], 'safe'],
            [['day', 'time'], 'string', 'max' => 50],
            [['session_id'], 'exist', 'skipOnError' => true, 'targetClass' => TutorSession::className(), 'targetAttribute' => ['session_id' => 'id']],
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
            'day' => 'Day',
            'time' => 'Time',
            'created_at' => 'Created At',
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
}
