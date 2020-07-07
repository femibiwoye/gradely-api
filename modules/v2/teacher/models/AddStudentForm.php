<?php
namespace app\modules\v2\teacher\models;

use Yii;
use yii\base\Model;
use app\modules\v2\models\{Classes, SignupForm};
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class AddStudentForm extends Model {
	public $class_id;
	public $password;
	public $students;

	public function rules()
	{
		return [
			[['class_id', 'password', 'students'], 'required'],
			['class_id', 'integer'],
			['password', 'string', 'min' => 6],
			[['class_id'], 'exist', 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
		];
	}

	public function addStudent($type, $student)
	{
		$form = new SignupForm(['scenario' => "$type-signup"]);
		$form->class = $this->class_id;
		$form->password = $this->password;
		$form->email = $student['email'];
		$form->phone = $student['phone'];
		$name_array = explode(' ', $student['name'], 2);
		$form->first_name = $name_array[0];
		$form->last_name = isset($name_array[1]) ? $name_array[1]: '';
		$form->country = SharedConstant::DEFAULT_COUNTRY;
		if (!$form->validate()) {
			print_r($form->getErrors());
			die();
			return false;
        }

        if (!$user = $form->signup($type)) {
            return false;
        }

        $user->updateAccessToken();
        return true;
	}

	public function addStudents($type)
	{
		foreach ($this->students as $student) {
			if (!$this->addStudent($type, $student)) {
				return false;
			}
		}

		return true;
	}
}
