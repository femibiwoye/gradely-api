<?php

namespace app\modules\v2\models;

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
 * @property Classes $class
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

    public function rules()
    {
        return [
            [['student_id', 'school_id'], 'required'],
            [['student_id', 'school_id', 'class_id', 'status'], 'integer'],
            [['created_at'], 'safe'],
            [['invite_code'], 'string', 'max' => 20],
            [['student_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']],
            [['class_id'], 'exist', 'skipOnError' => true, 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
        ];
    }

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

    public function fields()
    {
        $fields = parent::fields();
        $fields['class'] = 'class';

        return $fields;
    }

    public function getStudent()
    {
        return $this->hasOne(Student::className(), ['id' => 'student_id']);
    }

    public function getClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id']);
    }

    public function getParents()
    {
        return $this->hasMany(Parents::className(), ['student_id' => 'student_id']);
    }
}
