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
 * @property int $current_class This is to know if this is current class of a child. 1 mean child is in this class, 0 means this is not current class of a child. Can be use for promoting of a child.
 * @property string|null $subscription_status Basic is lms, premium is lms with catchup
 * @property string $created_at
 * @property string|null $updated_at
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
            [['subscription_status'], 'string'],
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
        if ($this->isRelationPopulated('class'))
            $fields['class'] = 'class';
        if ($this->isRelationPopulated('school'))
            $fields['school'] = 'school';

        return $fields;
    }

    public function getStudent()
    {
        return $this->hasOne(Student::className(), ['id' => 'student_id']);
    }

    public function getSchool()
    {
        return $this->hasOne(Schools::className(), ['id' => 'school_id'])->select(['id','name','slug','abbr','logo']);
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
