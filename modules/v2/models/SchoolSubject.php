<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "school_subject".
 *
 * @property int $id
 * @property int $school_id
 * @property int $subject_id
 * @property int $status
 * @property string $created_at
 * @property string $custom_subject_name
 *
 * @property Schools $school
 * @property Subjects $subject
 */
class SchoolSubject extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_subject';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['school_id', 'subject_id'], 'required'],
            [['school_id', 'subject_id', 'status'], 'integer'],
            [['custom_subject_name'], 'string'],
            [['created_at'], 'safe'],
            [['school_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
            [['subject_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'school_id' => 'School ID',
            'subject_id' => 'Subject ID',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[School]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchool()
    {
        return $this->hasOne(Schools::className(), ['id' => 'school_id']);
    }

    /**
     * Gets query for [[Subject]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }
}
