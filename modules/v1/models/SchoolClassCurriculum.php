<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "school_class_curriculum".
 *
 * @property int $id
 * @property int $school_id
 * @property int $class_id
 * @property int $subject_id
 * @property int $week_number It contains numbers. 1 stands for week one, 5 stands for week 5
 * @property int $topic_id
 * @property string $term
 * @property int $year
 * @property int $status
 * @property string $created_at
 *
 * @property Schools $school
 */
class SchoolClassCurriculum extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_class_curriculum';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['school_id', 'class_id', 'subject_id', 'week_number', 'topic_id', 'term', 'year'], 'required'],
            [['school_id', 'class_id', 'subject_id', 'week_number', 'topic_id', 'year', 'status'], 'integer'],
            [['term'], 'string'],
            [['created_at'], 'safe'],
            [['school_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
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
            'class_id' => 'Class ID',
            'subject_id' => 'Subject ID',
            'week_number' => 'Week Number',
            'topic_id' => 'Topic ID',
            'term' => 'Term',
            'year' => 'Year',
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
}
