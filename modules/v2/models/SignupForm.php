<?php

namespace app\modules\v2\models;


use app\modules\v2\components\{InputNotification, SharedConstant, Pricing};
use Yii;
use yii\base\Model;

class SignupForm extends Model
{

    public $first_name;
    public $last_name;
    public $phone;
    public $username;
    public $email;
    public $password;
    public $school_name;
    public $class;
    public $country;

    public function rules()
    {
        return [
            [['first_name', 'last_name', 'password'], 'required'],
            [['first_name', 'last_name', 'password'], 'filter', 'filter' => 'trim'],

            ['email', 'trim'],
            ['email', 'email', 'message' => 'Provide a valid email address'],
            ['email', 'string', 'min' => 8, 'max' => 50],
            [['first_name', 'last_name'], 'string', 'min' => 3],

            ['email', 'unique', 'targetClass' => 'app\modules\v2\models\User', 'message' => 'This email address has already been taken.'],
            ['email', 'match', 'pattern' => "/^[@a-zA-Z0-9+._-]+$/", 'message' => "Email can only contain letters, numbers or any of these special characters [@._-]"],

            ['phone', 'trim'],
            ['phone', 'unique', 'targetClass' => 'app\modules\v2\models\User', 'message' => 'This phone number has already been taken.'],
            ['phone', 'string', 'min' => 11, 'max' => 14],
            ['phone', 'match', 'pattern' => '/(^[0]\d{10}$)|(^[\+]?[234]\d{12}$)/'],

            ['password', 'string', 'min' => 4],

            [['email', 'phone'], 'required', 'on' => 'teacher-signup'],
            [['email', 'phone'], 'required', 'on' => 'parent-signup'],
            [['email', 'phone'], 'required', 'on' => 'tutor-signup'],

            [['school_name', 'email', 'phone', 'country'], 'required', 'on' => 'school-signup'],

            [['class', 'country'], 'required', 'on' => 'student-signup'],
            [['email'], 'safe', 'on' => 'student-signup'],

            [['class'], 'required', 'on' => 'parent-student-signup'],
            //[['email', 'country'], 'safe', 'on' => 'parent-student-signup'],
        ];
    }

    /**
     * This is the main signup starting point
     *
     * @param $type
     * @return User|bool
     * @throws \yii\db\Exception
     */
    public function signup($type)
    {
        $user = new User;
        $user->firstname = $this->first_name;
        $user->lastname = $this->last_name;
        if (!empty($this->phone))
            $user->phone = $this->phone;
        $user->type = $type;
        if (!empty($this->email))
            $user->email = $this->email;
        $user->class = $this->class;
        $user->setPassword($this->password);
        $user->generatePasswordResetToken();
        $user->generateAuthKey();
        $user->generateVerificationKey();
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$user->save() || !$this->generateCode($user) || !$this->createProfile($user)) {
                return false;
            }

            // Notification to welcome user
            $notification = new InputNotification();
            $notification->NewNotification('welcome_' . $user->type, [[$user->type . '_id', $user->id]]);


            $dbtransaction->commit();
        } catch (Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }

        return $user;
    }

    /**
     * This create a space for the registered user profile table
     * @param $user
     * @return bool
     */
    private function createProfile($user)
    {
        $model = new UserProfile();
        $model->user_id = $user->id;
        $model->country = $this->country;
        if ($user->type == 'school') {
            $this->createSchool($user);
        } else {
            Pricing::ActivateTrial($user->id, $user->type);
        }

        return $model->save();
    }

    /**
     * This create school calender record for newly registered school
     * @param $school
     * @return bool
     */
    private function createCalendar($school)
    {
        $model = new SchoolCalendar();
        $model->school_id = $school->id;
        $model->session_name = date('Y');
        $model->year = date('Y');
        $model->first_term_start = Yii::$app->params['first_term_start'];
        $model->first_term_end = Yii::$app->params['first_term_end'];
        $model->second_term_start = Yii::$app->params['second_term_start'];
        $model->second_term_end = Yii::$app->params['second_term_end'];
        $model->third_term_start = Yii::$app->params['third_term_start'];
        $model->third_term_end = Yii::$app->params['third_term_end'];
        return $model->save();
    }

    /**
     * Crease school record on signup
     * @param $user
     */
    private function createSchool($user)
    {
        $school = new Schools(['scenario' => 'school_signup']);
        $school->user_id = $user->id;
        $school->name = $this->school_name;
        $school->abbr = $this->extractAbbr();
        $school->country = $this->country;
        $school->save();
        $this->createCalendar($school);
        $this->createCurriculum($school->id);
//        Pricing::ActivateTrial($school->id, 'school'); //Temporarily commented pending we want want school trial to come back
    }

    private function createCurriculum($school_id)
    {
        $model = new SchoolCurriculum;
        $model->school_id = $school_id;
        $model->curriculum_id = SharedConstant::DEFAULT_CURRICULUM;
        if (!$model->save()) {
            return false;
        }

        return true;
    }

    /**
     * The return school name abbreviation
     * @return string
     */
    private function extractAbbr()
    {
        $name = \yii\helpers\Inflector::slug($this->school_name);
        $abbr = explode('-', $name, 2);
        $str2 = isset($abbr[1]) ? substr($abbr[1], 0, 2) : '';
        $str1 = !empty($str2) ? substr($abbr[0], 0, 3) . $str2 : substr($abbr[0], 0, 5);
        return strtoupper($str1);
    }

    private function generateCode($user)
    {
        $code = GenerateString::widget(['length' => 3, 'type' => 'char']) . '/' . date('Y') . '/' . str_pad($user->id, 4, "0", STR_PAD_LEFT);
        $user->code = $code;
        if (!$user->save(false)) {
            return false;
        }

        return true;
    }
}