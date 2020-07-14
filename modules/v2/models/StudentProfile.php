<?php
namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class StudentProfile extends User {
	public $student_id;
	public $teacher_id;
	private $teacher_classes;

	public function rules()
	{
		return [
			[['teacher_id', 'student_id'], 'required'],
			['teacher_id', 'exist', 'targetClass' => User::className(), 'targetAttribute' => ['teacher_id' => 'id']],
			['student_id', 'exist', 'targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']],
			['student_id', 'checkStudentInTeacherClass'],
		];
	}

	public function fields()
	{
		return [
			'id',
			'firstname',
			'lastname',
			'profile' => 'userProfile',
			'remarks' => 'remarks',
			'homework',
			'completion_rate' => 'totalHomeworks',
		];
	}

	public function getHomework() {
		return $this->getHomeworks()->where(['teacher_id' => Yii::$app->user->id])->andWhere(['type' => 'homework'])->all();
	}

	public function getTotalHomeworks() {
		$attempted_questions = 0;
		foreach ($this->homework as $homework) {
			if ($homework->quizSummary && $homework->quizSummary->submit == SharedConstant::VALUE_ONE) {
				$attempted_questions = $attempted_questions + 1;
			}
		}

		return ($attempted_questions / count($this->homework)) * 100;
	}

	public function checkStudentInTeacherClass()
	{
		$teacher_classes = TeacherClass::find()->where(['teacher_id' => $this->teacher_id])->all();
		foreach ($teacher_classes as $teacher_class) {
			if (StudentSchool::find()->where(['class_id' => $teacher_class->class_id])->andWhere(['student_id' => $this->student_id])->one()) {
				return true;
			}
		}

		return false;
	}
}
