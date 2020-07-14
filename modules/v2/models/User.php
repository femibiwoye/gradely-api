<?php

namespace app\modules\v2\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\filters\RateLimitInterface;

class User extends ActiveRecord implements IdentityInterface, RateLimitInterface {
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;

    public $password;

    public static function tableName() {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['firstname', 'lastname', 'type'], 'required'],
            [['username', 'firstname', 'lastname', 'code', 'phone', 'image', 'type', 'auth_key', 'password_hash', 'password_reset_token', 'verification_token', 'token', 'oauth_uid'], 'string'],
            [['class', 'is_boarded'], 'integer'],

            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'email', 'message' => 'Provide a valid email address'],
            ['email', 'unique', 'targetAttribute' => ['email'], 'targetClass' => 'app\modules\v2\models\User', 'message' => 'This email address is already exit'],

            ['status', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
        ];
    }

    public function attributeLabels() {
        return [
            'firstname' => 'First name',
            'lastname' => 'Last name',
            'image' => 'Image',
            'email' => 'email',
            'username' => 'User name',
            'code' => 'Code',
        ];
    }

    public function fields() {
        return [
            'id',
            'code',
            'firstname',
            'lastname',
            'phone',
            'image' => 'image',
            'type',
            'email',
            'is_boarded',
            'token'
        ];
    }

    public function extraFields() {
        return [
            'status',
            'created_at',
            'updated_at',
        ];
    }

    public function getImage() {
        return Yii::getAlias('@webroot') . '/images/users/' . $this->image;
    }

    public function getUserPreference() {
        return $this->hasOne(UserPreference::className(), ['user_id' => 'id']);
    }

    public function getUserProfile() {
        return $this->hasOne(UserProfile::className(), ['user_id' => 'id']);
    }

    public function getRemarks() {
        return $this->hasOne(Remarks::className(), ['receiver_id' => 'id']);
    }

    public function getHomeworks() {
        return $this->hasMany(Homeworks::className(), ['student_id' => 'id']);
    }

    public static function findIdentity($id) {
        return static::findOne(['AND', ['id' => $id], ['!=', 'status', self::STATUS_DELETED]]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        return static::findOne(['token' => $token]);

        if ($user = static::findOne(['AND', ['token' => $token], ['!=', 'status', self::STATUS_DELETED]])) {
            /**
             * This token is expired if expiry date is greater than current time.
             **/
            $expires = strtotime("+60 second", strtotime($user->token_expires));
            if ($expires > time()) {
                $user->token_expires = date('Y-m-d H:i:s', strtotime("+1 month", time()));
                $user->save();
                return $user;
            } else {
                $user->token = null;
                $user->save();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function updateAccessToken() {
        $token = Yii::$app->security->generateRandomString(200);
        $this->token = $token;
        if (!$this->save(false)) {
            return false;
        }

        return $this->token;
    }

    public function resetAccessToken() {
        $model = static::findOne(['id' => Yii::$app->user->id]);
        if (!$model) {
            return false;
        }

        $model->token = Yii::$app->security->generateRandomString();
        if (!$model->save(false)) {
            return false;
        }

        return true;
    }

    public static function isPasswordResetTokenValid($token) {
        if (empty($token)) {
            return false;
        }

        $user = self::find()->andWhere(['password_reset_token' => $token])->one();
        if (!$user) {
            return false;
        }

        return $user->token_expires >= date('Y-m-d h:i:s',time());
    }

    public function generatePasswordResetToken() {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
        date_default_timezone_set("Africa/Lagos");
        $this->token_expires  = date('Y-m-d h:i:s',strtotime("+30 minute", time()));
    }

    public static function findByPasswordResetToken($token) {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
        ]);
    }

    public function getTeacherClass() {
        return $this->hasMany(TeacherClass::className(), ['teacher_id' => 'id']);
    }

    public function getClasses() {
        return $this->hasMany(Classes::className(), ['id' => 'class_id'])
                    ->via('teacherClass');
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function getPassword() {
        return $this->password;
    }

    public function removePasswordResetToken() {
        $this->password_reset_token = null;
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {
        $this->auth_key = Yii::$app->security->generateRandomString();

        return $this->auth_key;
    }

    public static function find() {
        return parent::find()->andWhere(['<>', 'user.status', self::STATUS_DELETED]);
    }

    public function getRateLimit($request, $action) {
        return [$this->rateLimit, 1]; // $rateLimit requests per second
    }

    public function loadAllowance($request, $action) {
        return [$this->allowance, $this->allowance_updated_at];
    }

    public function saveAllowance($request, $action, $allowance, $timestamp) {
        $this->allowance = $allowance;
        $this->allowance_updated_at = $timestamp;
        $this->save();
    }

    //This action is called before saving
    public function beforeSave($insert) {
        if ($this->isNewRecord) {
            $this->created_at = time();
            $this->updated_at = time();
            $this->status = self::STATUS_ACTIVE;
        } else {
            $this->updated_at = time();
        }

        return parent::beforeSave($insert);
    }


}