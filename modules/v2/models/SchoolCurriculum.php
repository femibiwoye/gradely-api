<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "school_curriculum".
 *
 * @property int $id
 * @property int $school_id
 * @property int $curriculum_id
 * @property string $created_at
 *
 * @property Schools $school
 * @property ExamType $curriculum
 */
class SchoolCurriculum extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_curriculum';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['school_id', 'curriculum_id'], 'required'],
            [['school_id', 'curriculum_id'], 'integer'],
            [['created_at'], 'safe'],
            [['school_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
            [['curriculum_id'], 'exist', 'skipOnError' => true, 'targetClass' => ExamType::className(), 'targetAttribute' => ['curriculum_id' => 'id']],
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
            'curriculum_id' => 'Curriculum ID',
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
     * Gets query for [[Curriculum]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCurriculum()
    {
        return $this->hasOne(ExamType::className(), ['id' => 'curriculum_id']);
    }
}
