<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "tutor_availability".
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $day Monday to Sunday
 * @property string|null $period Morning, afternoon or evening
 * @property int|null $status Is this period is still active or not
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property User $user
 */
class TutorAvailability extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tutor_availability';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['day', 'period'], 'string', 'max' => 50],
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
            'user_id' => 'User ID',
            'day' => 'Day',
            'period' => 'Period',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
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
