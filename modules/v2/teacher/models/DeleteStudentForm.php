<?php
namespace app\modules\v2\teacher\models;

use Yii;
use yii\base\Model;
use app\modules\v2\models\{Classes, StudentSchool, TeacherClass};

/**
 * Password reset request form
 */
class DeleteStudentForm extends Model {
	public $student_id;
	public $class_id;
	public $teacher_id;

	public function rules()
	{
		return [
			[['student_id', 'class_id', 'teacher_id'], 'required'],
			['class_id', 'exist', 'targetClass' => Classes::className(), 'targetAttribute' => 'id'],
			['teacher_id', 'exist', 'targetClass' => TeacherClass::className(), 'targetAttribute' => ['teacher_id' => 'teacher_id', 'class_id' => 'class_id'], 'message' => 'Class belong to other teacher'],
			['class_id', 'exist', 'targetClass' => StudentSchool::className(), 'targetAttribute' => ['class_id' => 'class_id', 'student_id' => 'student_id'], 'message' => 'Student does not exist in this class'],
		];
	}

	public function deleteStudent()
	{
		$student = StudentSchool::findOne(['student_id' => $this->student_id]);
		if (!$student->delete()) {
			return false;
		}

		return true;
	}
}
