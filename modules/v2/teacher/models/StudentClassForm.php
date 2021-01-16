<?php

namespace app\modules\v2\teacher\models;

use Yii;
use yii\base\Model;
use app\modules\v2\models\{Classes, User, StudentSchool, TeacherClass};

/**
 * Password reset request form
 */
class StudentClassForm extends Model
{
    public $class_id;
    public $teacher_id;
    private $studentDetail = [];
    public $status = 1;

    public function rules()
    {
        return [
            [['class_id', 'teacher_id'], 'required'],
            ['class_id', 'exist', 'targetClass' => Classes::className(), 'targetAttribute' => 'id'],
            ['class_id', 'exist', 'targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'status' => 'status'], 'message' => 'Class belongs to the other teacher'],
        ];
    }

    public function getStudents()
    {
        $students_in_class = (new \yii\db\Query())
            ->select(['user.id', 'user.code', 'user.firstname', 'user.lastname', 'user.image'])
            ->from('student_school')
            ->innerJoin('user', 'user.id = student_school.student_id')
            ->where(['student_school.class_id' => $this->class_id,'student_school.is_active_class'=>1,'student_school.status'=>1])
            ->all();

        return $students_in_class;
    }
}
