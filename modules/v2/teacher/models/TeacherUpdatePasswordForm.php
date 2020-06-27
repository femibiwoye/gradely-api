<?php
namespace app\modules\v2\teacher\models;

use app\modules\v2\models\User;
use Yii;
use yii\base\Model;

/**
 * Password reset request form
 */
class TeacherUpdatePasswordForm extends Model {
	public $new_password;
	public $confirm_new_password;
	public $current_password;
	public $user;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['new_password', 'confirm_new_password', 'current_password'], 'required'],
			['new_password', 'compare', 'compareAttribute' => 'confirm_new_password'],
			['current_password', 'validateCurrentPassword'],
		];
	}

	public function validateCurrentPassword() {
		if (!$this->user->validatePassword($this->current_password)) {
			$this->addError('Current password is incorrect!');
		}

		return true;
	}

	public function updatePassword() {
		$this->user->password_hash = Yii::$app->security->generatePasswordHash($this->new_password);
		if (!$this->user->save()) {
			return false;
		}

		return true;
	}
}
