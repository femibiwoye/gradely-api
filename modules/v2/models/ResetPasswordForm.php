<?php

namespace app\modules\v2\models;

use app\modules\v2\components\InputNotification;
use app\modules\v2\components\Utility;
use app\modules\v2\models\User;
use yii\base\Model;
use Yii;

/**
 * Password reset form
 */
class ResetPasswordForm extends Model
{
    public $confirm_password;
    public $password;
    public $token;

    /**
     * @var \common\models\User
     */
    private $_user;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['password', 'confirm_password', 'token'], 'required'],
            ['token', 'string'],
            ['password', 'string', 'min' => 6],
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
            ['token', function ($attribute, $params, $validator) {
                $this->_user = User::findByPasswordResetToken($this->$attribute);
                if (!$this->_user) {
                    $this->addError($attribute, 'Invalid or Expire Token');
                }
            }],
        ];
    }

    /**
     * Resets password.
     *
     * @return boolean if password was reset.
     */
    public function resetPassword()
    {
        $user = $this->_user;
        $user->setPassword($this->password);
        $user->removePasswordResetToken();

        if (!$user->save()) {
            return false;
        }

        $profile = UserProfile::findOne(['user_id'=>$user->id]);
        $location = Utility::UserLocation();
        $profile->password_updated_device = $_SERVER['HTTP_USER_AGENT'];
        $profile->password_updated_time = date('Y-m-d H:i:s');
        if (isset($location->city) && isset($location->country))
            $profile->password_updated_location = $location->city . ', ' . $location->region . ', ' . $location->country;
        $profile->update();

        $notification = new InputNotification();
        $notification->NewNotification('forgot_password', [['user_id', $user->id]]);

        return true;
    }
}
