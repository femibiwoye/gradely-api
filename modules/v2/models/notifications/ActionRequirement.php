<?php

namespace app\modules\v2\models\notifications;

use Yii;

/**
 * This is the model class for table "action_requirement".
 *
 * @property int $id
 * @property int $action_id
 * @property string $field_name
 * @property string $field_description
 * @property string $created_at
 *
 * @property Actions $action
 */
class ActionRequirement extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'action_requirement';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['action_id', 'field_name', 'field_description'], 'required'],
            [['action_id'], 'integer'],
            [['field_description'], 'string'],
            [['created_at'], 'safe'],
            [['field_name'], 'string', 'max' => 100],
            [['action_id'], 'exist', 'skipOnError' => true, 'targetClass' => Actions::className(), 'targetAttribute' => ['action_id' => 'id']],
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
            'field_description' => 'Field Description',
            'created_at' => 'Created At',
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
