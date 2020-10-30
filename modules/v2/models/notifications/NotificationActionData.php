<?php

namespace app\modules\v2\models\notifications;

use Yii;

/**
 * This is the model class for table "notification_action_data".
 *
 * @property int $id
 * @property int $action_id
 * @property string $field_name
 * @property string $field_value
 * @property int $notification_id
 *
 * @property Actions $action
 */
class NotificationActionData extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notification_action_data';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['action_id', 'field_name', 'field_value', 'notification_id'], 'required'],
            [['action_id', 'notification_id'], 'integer'],
            [['field_value'], 'string'],
            [['field_name'], 'string', 'max' => 100],
            [['action_id'], 'exist', 'skipOnError' => true, 'targetClass' => Actions::className(), 'targetAttribute' => ['action_id' => 'id']],
            [['notification_id'], 'exist', 'targetClass' => Notifications::className(), 'targetAttribute' => ['notification_id' => 'id']],
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
            'field_name' => 'Field Name',
            'field_value' => 'Field Value',
            'notification_id' => 'Notification ID',
        ];
    }

    /**
     * Gets query for [[Action]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAction()
    {
        return $this->hasOne(Actions::className(), ['id' => 'action_id']);
    }

    public static function getDb()
    {
        return Yii::$app->get('notification');
    }
}
