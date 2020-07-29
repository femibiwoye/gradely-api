<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "school_teachers".
 *
 * @property int $id
 * @property int $teacher_id
 * @property int $school_id
 * @property int $status
 * @property string $created_at
 *
 * @property Schools $school
 * @property User $teacher
 */
class SchoolTeachers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_teachers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'school_id'], 'required'],
            [['teacher_id', 'school_id', 'status'], 'integer'],
            [['created_at'], 'safe'],
            [['school_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
            [['teacher_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['teacher_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'teacher_id' => 'Teacher ID',
            'school_id' => 'School ID',
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
     * Gets query for [[Teacher]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(User::className(), ['id' => 'teacher_id']);
    }
}
