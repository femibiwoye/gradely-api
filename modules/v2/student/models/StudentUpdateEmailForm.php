<?php
namespace app\modules\v2\student\models;

use app\modules\v2\models\User;
use Yii;
use yii\base\Model;

/**
 * Password reset request form
 */
class StudentUpdateEmailForm extends Model {
	public $email;
	public $confirm_password;
	public $user;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			['email', 'filter', 'filter' => 'trim'],
			[['email', 'confirm_password'], 'required'],
			['email', 'email', 'message'=> 'Provide a valid email address.'],
			['email', 'string', 'min' => 8, 'max' => 64],
			['email', 'match', 'pattern' => "/^[@a-zA-Z0-9._-]+$/", 'message' => "Email can only contain letters, numbers or any of these special characters [@._-]"],
			['email', 'unique', 'targetClass' => User::className(), 'targetAttribute' => ['email' => 'email']],

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
