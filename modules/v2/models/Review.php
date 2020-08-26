<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "review".
 *
 * @property int $id
 * @property int $session_id
 * @property int $receiver_id
 * @property int $sender_id
 * @property int $rate Student rating tutor
 * @property string|null $review Student message on the rating
 * @property int|null $topic_taught Topic tutor taught the student
 * @property string|null $recommended_topic Tutor recommendation to the student
 * @property string|null $tutor_rate_student Tutor feedback to the student
 * @property string|null $tutor_comment Tutor comment on a student
 * @property string $created_at
 *
 * @property User $receiver
 * @property User $sender
 */
class Review extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'review';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['session_id', 'receiver_id', 'sender_id', 'rate'], 'required'],
            [['session_id', 'receiver_id', 'sender_id', 'rate', 'topic_taught'], 'integer'],
            [['review', 'tutor_comment'], 'string'],
            [['created_at'], 'safe'],
            [['recommended_topic', 'tutor_rate_student'], 'string', 'max' => 50],
            [['receiver_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['receiver_id' => 'id']],
            [['sender_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['sender_id' => 'id']],
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
            'receiver_id' => 'Receiver ID',
            'sender_id' => 'Sender ID',
            'rate' => 'Rate',
            'review' => 'Review',
            'topic_taught' => 'Topic Taught',
            'recommended_topic' => 'Recommended Topic',
            'tutor_rate_student' => 'Tutor Rate Student',
            'tutor_comment' => 'Tutor Comment',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Receiver]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReceiver()
    {
        return $this->hasOne(User::className(), ['id' => 'receiver_id']);
    }

    /**
     * Gets query for [[Sender]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSender()
    {
        return $this->hasOne(User::className(), ['id' => 'sender_id']);
    }
}
