<?php
namespace app\modules\v2\teacher\models;

use app\modules\v2\models\User;
use Yii;
use yii\base\Model;

/**
 * Password reset request form
 */
class TeacherUpdatePhoneForm extends Model {
	public $phone;
	public $confirm_password;
	public $user;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			['phone', 'filter', 'filter' => 'trim'],
			[['phone', 'confirm_password'], 'required'],
            ['phone', 'string', 'min' => 11, 'max' => 14],
            ['phone', 'match', 'pattern' => '/(\d{10}$)|(^[\+]?[234]\d{12}$)/'],
			['phone', 'unique', 'targetClass' => User::className(), 'targetAttribute' => ['phone' => 'phone']],
			['confirm_password', 'validateConfirmPassword'],
		];
	}

	public function validateConfirmPassword() {
		if (!$this->user->validatePassword($this->confirm_password)) {
			$this->addError('confirm_password', 'Password is incorrect!');
			return false;
		}

		return true;
	}

	public function sendEmail() {
		return true;
		//will populate this function in the future
	}
}
