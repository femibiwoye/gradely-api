<?php
namespace app\modules\v2\teacher\models;

use Yii;
use yii\base\Model;
use app\modules\v2\models\{Classes, User, StudentSchool, TeacherClass};

/**
 * Password reset request form
 */
class StudentClassForm extends Model {
	public $class_id;
	public $teacher_id;
	private $studentDetail = [];

	public function rules() {
		return [
			[['class_id', 'teacher_id'], 'required'],
			['class_id', 'exist', 'targetClass' => Classes::className(), 'targetAttribute' => 'id'],
			['class_id', 'exist', 'targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id'], 'message' => 'Class belongs to the other teacher'],
		];
	}

	public function getStudents() {
		$class = Classes::findOne(['id' => $this->class_id]);
		if (empty($class->studentSchool)) {
			return 'No student available';
		}

		foreach ($class->studentSchool as $student) {
			array_push($this->studentDetail, $student->student);
		}

		return $this->studentDetail;
	}
}
