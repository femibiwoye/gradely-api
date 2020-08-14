<?php

namespace app\modules\v2\student\models;

use app\modules\v2\models\{User, UserProfile};
use Yii;
use yii\base\Model;

/**
 * Password reset request form
 */
class UpdateStudentForm extends Model
{
    public $firstname;
    public $lastname;
    public $image;
    public $gender;
    public $phone;
    public $birth_date;
    public $address;
    public $street;
    public $country;
    public $state;
    public $city;
    public $postal_code;
    public $about;
    public $email;
    public $user;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['firstname', 'lastname'], 'required'],
            [['firstname', 'lastname'], 'filter', 'filter' => 'trim'],
            //[['dob', 'mob', 'yob'], 'integer'],
            [['birth_date'], 'date', 'format' => 'dd-mm-yyyy'],
            [['gender'], 'string', 'max' => 50],
            ['email', 'email'],
            ['email', 'unique', 'targetClass' => 'app\modules\v2\models\User', 'message' => 'This email has already been taken.', 'when' => function($model) {
                return $this->user->email != $this->email;
            }],
            [['address', 'street', 'state', 'city', 'country', 'postal_code', 'about', 'image'], 'string', 'max' => 255],

            ['phone', 'trim'],
            ['phone', 'string', 'min' => 11, 'max' => 14],
            ['phone', 'match', 'pattern' => '/(^[0]\d{10}$)|(^[\+]?[234]\d{12}$)/'],
            ['phone', 'unique', 'targetClass' => 'app\modules\v2\models\User', 'message' => 'This phone number has already been taken.', 'when' => function ($model) {
                return $this->user->phone != $this->phone;
            }],
        ];
    }


    public function updateStudent()
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model = $this->updateStudentUser()) {
                return false;
            }

            if (!$this->updateStudentUserProfile()) {
                return false;
            }

            $dbtransaction->commit();
        } catch (Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }
        return $model;
    }

    private function updateStudentUser()
    {
        $this->user->attributes = $this->attributes;
        if (!$this->user->save()) {
            return false;
        }

        return $this->user;
    }

    private function updateStudentUserProfile()
    {
        $user_profile = UserProfile::find()->where(['user_id' => $this->user->id])->one();
        $date = strtotime($this->birth_date);
        $user_profile->dob = date('d', $date);
        $user_profile->mob = date('m', $date);
        $user_profile->yob = date('Y', $date);
        $user_profile->attributes = $this->attributes;
        if (!$user_profile->save()) {
            return false;
        }

        return true;
    }
}
