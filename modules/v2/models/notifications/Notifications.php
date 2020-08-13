<?php

namespace app\modules\v2\models\notifications;

use Yii;

/**
 * This is the model class for table "notifications".
 *
 * @property int $id
 * @property int|null $action_id
 * @property string|null $action_name
 * @property string $receiver_type
 * @property string|null $raw_data this contains the raw json file
 * @property int $is_time_bound 0 means no time bound, 1 means it is time bound
 * @property string|null $start_time
 * @property string|null $end_date
 * @property int $status
 * @property string $created_at
 *
 * @property Links[] $links
 */
class Notifications extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notifications';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['action_id', 'is_time_bound', 'status'], 'integer'],
            [['receiver_type'], 'required'],
            [['receiver_type', 'raw_data'], 'string'],
            [['start_time', 'end_date', 'created_at'], 'safe'],
            [['action_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'action_id' => 'Action ID',
            'action_name' => 'Action Name',
            'receiver_type' => 'Receiver Type',
            'raw_data' => 'Raw Data',
            'is_time_bound' => 'Is Time Bound',
            'start_time' => 'Start Time',
            'end_date' => 'End Date',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Links]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLinks()
    {
        return $this->hasMany(Links::className(), ['notification_id' => 'id']);
    }

    public static function getDb()
    {
        return Yii::$app->get('notification');
    }
}
