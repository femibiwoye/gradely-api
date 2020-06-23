<?php
namespace app\modules\v2\models;

use app\modules\v2\models\User;
use Yii;
use yii\base\Model;

/**
 * Password reset request form
 */
class PasswordResetRequestForm extends Model {
	public $email;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			['email', 'filter', 'filter' => 'trim'],
			['email', 'required'],
			['email', 'email', 'message'=> 'Provide a valid email address.'],
			['email', 'string', 'min' => 8, 'max' => 64],
			['email', 'match', 'pattern' => "/^[@a-zA-Z0-9._-]+$/", 'message' => "Email can only contain letters, numbers or any of these special characters [@._-]"],
			['email', 'exist', 'targetClass' => User::className()],
		];
	}

	/**
	 * Sends an email with a link, for resetting the password.
	 *
	 * @return boolean whether the email was send
	 */
	public function sendEmail() {
		/* @var $user User */
		$user = User::find()
				->where(['email' => $this->email])
				->one();

		if (!$user) {
			return false;
		}

		if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
			$user->generatePasswordResetToken();
		}
		
		if (!$user->save(false)) {
			return false;
		}

		//TODO::will send an email here.

		return true;
	}
}
