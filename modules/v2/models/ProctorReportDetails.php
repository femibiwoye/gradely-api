<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "proctor_report_details".
 *
 * @property int $id
 * @property int|null $report_id
 * @property int $user_id
 * @property int $assessment_id
 * @property string $file_type
 * @property string $name
 * @property string $extension
 * @property int|null $integrity This holds the integrity score of a practice
 * @property string $url
 * @property string|null $raw
 * @property string $created_at
 *
 * @property ProctorReport $report
 */
class ProctorReportDetails extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'proctor_report_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['report_id', 'user_id', 'assessment_id', 'integrity'], 'integer'],
            [['user_id', 'assessment_id', 'file_type', 'name', 'extension', 'url'], 'required'],
            [['file_type', 'name', 'url', 'raw'], 'string'],
            [['created_at'], 'safe'],
            [['extension'], 'string', 'max' => 50],
            [['report_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProctorReport::className(), 'targetAttribute' => ['report_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'report_id' => 'Report ID',
            'user_id' => 'User ID',
            'assessment_id' => 'Assessment ID',
            'file_type' => 'File Type',
            'name' => 'Name',
            'extension' => 'Extension',
            'integrity' => 'Integrity',
            'url' => 'Url',
            'raw' => 'Raw',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Report]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getReport()
    {
        return $this->hasOne(ProctorReport::className(), ['id' => 'report_id']);
    }
}