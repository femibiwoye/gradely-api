<?php

namespace app\modules\v2\models;

use Yii;
use yii\base\Model;

/**
 * Login is the model behind the login.
 *
 * @property User|null $user This property is read-only.
 *
 */
class Login extends Model
{
    //email could also be code or phone number
    public $email;
    public $password;

    private $_user = false;
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['email', 'password'], 'required'],
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Incorrect login details.');
            }

            return true;
        }
    }

    /**
     * Logs in a user using the provided email or code or phone number and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if (!$this->validate()) {
            return false;
        }

        return $this->getUser();
    }

    /**
     * Finds user by [[email]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::find()
                ->where(['AND', ['!=', 'status', self::STATUS_ACTIVE], ['OR', ['email' => $this->email], ['phone' => $this->email], ['code' => $this->email]]])
                ->one();
        }

        return $this->_user;
    }
}
