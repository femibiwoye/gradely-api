<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

/**
 * This is the model class for table "proctor_feedback".
 *
 * @property int $id
 * @property int $user_id
 * @property int $proctor_id
 * @property int $assessment_id
 * @property string|null $type
 * @property string|null $report They will be comma separated values. e.g Cheating, Impersonation
 * @property string|null $details
 * @property string|null $created_at
 */
class ProctorFeedback extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proctor_feedback';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'proctor_id', 'assessment_id','type','report','details'], 'required'],
            [['user_id', 'proctor_id', 'assessment_id'], 'integer'],
            [['type', 'report', 'details'], 'string'],
            [['type'], 'in', 'range' => SharedConstant::PROCTOR_FEEDBACK_TYPE],
            [['created_at'], 'safe'],
            [['user_id'], 'exist', 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['proctor_id'], 'exist', 'targetClass' => ProctorReport::className(), 'targetAttribute' => ['proctor_id' => 'id','assessment_id']],
            [['assessment_id'], 'exist', 'targetClass' => Homeworks::className(), 'targetAttribute' => ['assessment_id' => 'id']],
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
            'proctor_id' => 'Proctor ID',
            'assessment_id' => 'Assessment ID',
            'type' => 'Type',
            'report' => 'Report',
            'details' => 'Details',
            'created_at' => 'Created At',
        ];
    }
}
