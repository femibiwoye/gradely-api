<?php

namespace app\modules\v2\models\notifications;

use Yii;

/**
 * This is the model class for table "reminder".
 *
 * @property int $id
 * @property string|null $receivers_id
 * @property string|null $reminder_interval
 * @property int|null $action_id
 * @property int|null $status
 */
class Reminder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reminder';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['receivers_id'], 'string'],
            [['action_id', 'status'], 'integer'],
            [['reminder_interval'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'receivers_id' => 'Receivers ID',
            'reminder_interval' => 'Reminder Interval',
            'action_id' => 'Action ID',
            'status' => 'Status',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->get('notification');
    }
}
