<?php

namespace app\modules\v2\models\campaign;

use Yii;

/**
 * This is the model class for table "campaign_live_class".
 *
 * @property int $id
 * @property string $tutor_name
 * @property string|null $tutor_image
 * @property string $tutor_email
 * @property string|null $tutor_access
 * @property string $class_name
 * @property string|null $start_at
 * @property string|null $ended_at
 * @property string|null $status
 */
class CampaignLiveClass extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'campaign_live_class';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tutor_name', 'tutor_email', 'class_name'], 'required'],
            [['tutor_image', 'status'], 'string'],
            [['start_at', 'ended_at'], 'safe'],
            [['tutor_name', 'tutor_email', 'tutor_access'], 'string', 'max' => 50],
            [['class_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tutor_name' => 'Tutor Name',
            'tutor_image' => 'Tutor Image',
            'tutor_email' => 'Tutor Email',
            'tutor_access' => 'Tutor Access',
            'class_name' => 'Class Name',
            'start_at' => 'Start At',
            'ended_at' => 'Ended At',
            'status' => 'Status',
        ];
    }
}
