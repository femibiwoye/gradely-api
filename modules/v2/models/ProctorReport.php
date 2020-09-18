<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "proctor_report".
 *
 * @property int $id
 * @property int $assessment_id
 * @property int $student_id
 * @property int|null $integrity
 * @property string $assessment_type
 * @property string $created_at
 *
 * @property Homeworks $assessment
 * @property User $student
 * @property ProctorReportDetails[] $proctorReportDetails
 */
class ProctorReport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proctor_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['assessment_id', 'student_id'], 'required'],
            [['assessment_id', 'student_id', 'integrity'], 'integer'],
            [['created_at'], 'safe'],
            [['assessment_type'], 'string', 'max' => 40],
            [['assessment_id'], 'exist', 'skipOnError' => true, 'targetClass' => Homeworks::className(), 'targetAttribute' => ['assessment_id' => 'id']],
            [['student_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'assessment_id' => 'Assessment ID',
            'student_id' => 'Student ID',
            'integrity' => 'Integrity',
            'assessment_type' => 'Assessment Type',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Assessment]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAssessment()
    {
        return $this->hasOne(Homeworks::className(), ['id' => 'assessment_id']);
    }

    /**
     * Gets query for [[Student]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudent()
    {
        return $this->hasOne(User::className(), ['id' => 'student_id']);
    }

    /**
     * Gets query for [[ProctorReportDetails]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProctorReportDetails()
    {
        return $this->hasMany(ProctorReportDetails::className(), ['report_id' => 'id']);
    }
}
