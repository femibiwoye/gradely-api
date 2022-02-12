<?php

namespace app\modules\v2\teacher\models;

use app\modules\v2\models\{User, UserProfile};
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * Password reset request form
 */
class UpdateTeacherForm extends Model
{
    public $firstname;
    public $lastname;
    public $image;
    public $gender;
    public $phone;
//    public $dob;
//    public $mob;
//    public $yob;
    public $birth_date;
    public $address;
    public $street;
    public $country;
    public $state;
    public $city;
    public $postal_code;
    public $about;
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
            [['address', 'street', 'state', 'city', 'country', 'postal_code', 'about', 'image'], 'string', 'max' => 255],

            ['phone', 'trim'],
            ['phone', 'string', 'min' => 11, 'max' => 14],
            ['phone', 'match', 'pattern' => '/(^[0]\d{10}$)|(^[\+]?[234]\d{12}$)/'],
            ['phone', 'unique', 'targetClass' => 'app\modules\v2\models\User', 'message' => 'This phone number has already been taken.', 'when' => function ($model) {
                return $this->user->phone != $this->phone;
            }],
            //['image', 'imageProcessing'],
        ];
    }


    public function updateTeacher()
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model = $this->updateTeacherUser()) {
                return false;
            }

            if (!$this->updateTeacherUserProfile()) {
                return false;
            }

            $dbtransaction->commit();
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            \Sentry\captureException($e);
            return false;
        }
        return $model;
    }

    private function updateTeacherUser()
    {
        $this->user->attributes = $this->attributes;
        if (!$this->user->save()) {
            return false;
        }

        return $this->user;
    }

    private function updateTeacherUserProfile()
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

    public function imageProcessing()
    {
        if (!isset($this->image)) {
            return true;
        }

        $img = UploadedFile::getInstance($this->user, 'image');
        $imageName = 'user_' . $this->user->id . '.' . $img->getExtension();
        $img->saveAs(Yii::getAlias('@webroot') . '/images/users/' . $imageName);
        return $this->image = $imageName;
    }
}
