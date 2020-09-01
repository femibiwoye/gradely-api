<?php
namespace app\modules\v2\teacher\models;

use Yii;
use yii\base\Model;
use app\modules\v2\models\{Classes, Schools, TeacherClass};

/**
 * Password reset request form
 */
class TeacherSchoolForm extends Model {
	public $school_id;
	public $class_id;
	public $teacher_id;
	
	public function rules() {
		return [
			[['school_id', 'class_id'], 'required'],
			['class_id', 'exist', 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id', 'school_id' => 'school_id']],
			['school_id', 'exist', 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
		];
	}

	public function addTeacherClass($code = 1) {
		$model = new TeacherClass;
		$model->attributes = $this->attributes;
		$model->status = $code;
		if (!$model->save()) {
			return false;
		}

		//Check and add teacher to school_teacher is not exists
		$model->addSchoolTeacher($code);
		return $model;
	}
}
