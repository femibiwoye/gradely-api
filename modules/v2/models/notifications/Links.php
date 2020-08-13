<?php

namespace app\modules\v2\models\notifications;

use Yii;

/**
 * This is the model class for table "links".
 *
 * @property int $id
 * @property int $notification_id
 * @property string $token should be unique
 * @property string $destination The actual url where this token should go
 * @property int $click_count
 * @property string $created_at
 * @property string|null $last_click Last time someone clicked on this link
 * @property int $status if link is still available or not
 * @property int|null $out_logging_id out logging id
 *
 * @property Notifications $notification
 */
class Links extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'links';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['notification_id', 'token', 'destination'], 'required'],
            [['notification_id', 'click_count', 'status', 'out_logging_id'], 'integer'],
            [['destination'], 'string'],
            [['created_at', 'last_click'], 'safe'],
            [['token'], 'string', 'max' => 255],
            [['token'], 'unique'],
            [['notification_id'], 'exist', 'skipOnError' => true, 'targetClass' => Notifications::className(), 'targetAttribute' => ['notification_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'notification_id' => 'Notification ID',
            'token' => 'Token',
            'destination' => 'Destination',
            'click_count' => 'Click Count',
            'created_at' => 'Created At',
            'last_click' => 'Last Click',
            'status' => 'Status',
            'out_logging_id' => 'Out Logging ID',
        ];
    }

    /**
     * Gets query for [[Notification]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotification()
    {
        return $this->hasOne(Notifications::className(), ['id' => 'notification_id']);
    }

    public static function getDb()
    {
        return Yii::$app->get('notification');
    }
}
