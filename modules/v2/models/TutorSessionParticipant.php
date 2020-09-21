<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "tutor_session_participant".
 *
 * @property int $id
 * @property int $session_id
 * @property int $participant_id Expected to be student id
 * @property string $created_at
 *
 * @property TutorSession $session
 */
class TutorSessionParticipant extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tutor_session_participant';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['session_id', 'participant_id'], 'required'],
            [['session_id', 'participant_id'], 'integer'],
            [['created_at'], 'safe'],
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
            'participant_id' => 'Participant ID',
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
