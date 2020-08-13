<?php

namespace app\modules\v2\models\notifications;

use Yii;

/**
 * This is the model class for table "actions".
 *
 * @property int $id
 * @property string $name a unique name for different types of emails
 * @property string $scenario
 * @property string $receiver_type
 * @property string $created_at
 *
 * @property ActionRequirement[] $actionRequirements
 * @property NotificationActionData[] $notificationActionDatas
 */
class Actions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'actions';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'scenario', 'receiver_type'], 'required'],
            [['scenario', 'receiver_type'], 'string'],
            [['created_at'], 'safe'],
            [['name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'scenario' => 'Scenario',
            'receiver_type' => 'Receiver Type',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[ActionRequirements]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getActionRequirements()
    {
        return $this->hasMany(ActionRequirement::className(), ['action_id' => 'id']);
    }

    /**
     * Gets query for [[NotificationActionDatas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotificationActionDatas()
    {
        return $this->hasMany(NotificationActionData::className(), ['action_id' => 'id']);
    }

    public static function getDb()
    {
        return Yii::$app->get('notification');
    }
}
