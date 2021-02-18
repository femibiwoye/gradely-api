<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "homework_selected_student".
 *
 * @property int $id
 * @property int $homework_id
 * @property int $student_id
 * @property int|null $teacher_id
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Homeworks $homework
 * @property User $student
 * @property User $teacher
 */
class HomeworkSelectedStudent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'homework_selected_student';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['homework_id', 'student_id'], 'required'],
            [['homework_id', 'student_id', 'teacher_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['homework_id'], 'exist', 'skipOnError' => true, 'targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id']],
            [['student_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']],
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
            'homework_id' => 'Homework ID',
            'student_id' => 'Student ID',
            'teacher_id' => 'Teacher ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Homework]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHomework()
    {
        return $this->hasOne(Homeworks::className(), ['id' => 'homework_id']);
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
     * Gets query for [[Teacher]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(User::className(), ['id' => 'teacher_id']);
    }
}
