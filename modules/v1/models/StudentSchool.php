<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "student_school".
 *
 * @property int $id
 * @property int $student_id
 * @property int $school_id
 * @property int|null $class_id
 * @property string|null $invite_code
 * @property int $status
 * @property string $created_at
 *
 * @property User $student
 */
class StudentSchool extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'student_school';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['student_id', 'school_id'], 'required'],
            [['student_id', 'school_id', 'class_id', 'status'], 'integer'],
            [['created_at'], 'safe'],
            [['invite_code'], 'string', 'max' => 20],
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
            'student_id' => 'Student ID',
            'school_id' => 'School ID',
            'class_id' => 'Class ID',
            'invite_code' => 'Invite Code',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
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
}
