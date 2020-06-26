<?php

namespace app\modules\v2\models;

use Yii;
use yii\base\Model;

class SignupForm extends Model {
	public $first_name;
	public $last_name;
	public $school_name;
	public $image;
	public $email;
	public $password;
	public $confirm_password;
	public $country_code;
	public $phone;
	public $type;
	public $class;

	public function rules() {
		return [
			[['first_name', 'last_name', 'email', 'password', 'confirm_password', 'type'], 'required'],

			[['school_name', 'type', 'image', 'class'], 'string'],
			[['type'], 'validateSignupType'],
			[['first_name', 'last_name', 'school_name', 'email'], 'filter', 'filter' => 'trim'],
			[['first_name', 'last_name'], 'string', 'min' => 1, 'max' => 32],

			['email', 'filter', 'filter' => 'trim'],
			['email', 'email', 'message' => 'Provide a valid email address'],
			['email', 'string', 'min' => 8, 'max' => 32],
			['email', 'unique', 'targetClass' => 'app\modules\v2\models\User', 'message' => 'This email address has already been taken'],
			['email', 'match', 'pattern' => "/^[@a-zA-Z0-9._-]+$/", 'message' => "Email can only contain letters, numbers or any of these special characters [@._-]"],
			['email', 'unique', 'targetClass' => 'app\modules\v2\models\User', 'targetAttribute' => ['email'], 'message' => 'Email is already existing'],

			[['password', 'confirm_password'], 'string', 'min' => 6],
			['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'password does not match' ],

			['country_code', 'string', 'min' => 2, 'max' => 3],
			['country_code', 'filter', 'filter' => 'strtoupper'],
			['phone', \miserenkov\validators\PhoneValidator::className(), 'countryAttribute'=> 'country_code'],
			['phone', 'unique', 'targetClass' => 'app\modules\v2\models\User', 'targetAttribute' => 'phone', 'message' => 'Contact number is already exist'],
		];
	}

	public function validateSignupType() {
		if ($this->type == SharedConstant::SCHOOL_TYPE && empty($this->school_name)) {
			$this->addError('School name cannot be blank');
		}

		return true;
	}


	public function signup() {
		$user = new User;
		$user->firstname = $this->first_name;
		$user->lastname = $this->last_name;
		$user->phone = $this->phone;
		$user->image = $this->image;
		$user->type = $this->type;
		$user->email = $this->email;
		$user->class = $this->class;
		$user->setPassword($this->password);
		$user->generatePasswordResetToken();
		$user->generateAuthKey();
		$dbtransaction = Yii::$app->db->beginTransaction();
		try {
			if (!$user->save(false) || !$this->generateCode($user)) {
				return false;
			}

			$dbtransaction->commit();
		} catch (Exception $e) {
			$dbtransaction->rollBack();
			return false;
		}

		return $user;
	}

	public function generateCode($user) {
		$code = GenerateString::widget(['length' => 3, 'type' => 'char']) . '/' . date('Y') . '/' . str_pad($user->id, 4, "0", STR_PAD_LEFT);
        $user->code = $code;
        if (!$user->save(false)) {
        	return false;
        }

        return true;
	}
}