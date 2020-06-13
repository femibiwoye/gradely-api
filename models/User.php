<?php
 
namespace app\models;
 
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\filters\RateLimitInterface;
 
/**
 * User model
 *
* @property int $id
 * @property string|null $username
 * @property string|null $code
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $phone
 * @property string|null $image
 * @property string $type
 * @property string $auth_key
 * @property string $password_hash
 * @property string|null $password_reset_token
 * @property string|null $email
 * @property int|null $class This is student temporary class while the child is yet to be connected to school
 * @property int $status 10 for active, 9 for inactive and 0 for deleted
 * @property string|null $subscription_expiry
 * @property string|null $subscription_plan
 * @property int $created_at
 * @property int $updated_at
 * @property string|null $verification_token
 * @property string|null $oauth_provider
 * @property string|null $token
 * @property string|null $token_expires
 * @property string|null $oauth_uid
 * @property string|null $last_accessed Last time the website  was accessed
 *
 * @property SecurityQuestionAnswer[] $securityQuestionAnswers
 * @property StudentSchool[] $studentSchools
 */
class User extends ActiveRecord implements IdentityInterface, RateLimitInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    const SCENERIO_UPDATE_SCHOOL_EMAIL = 'update_scholl_email';
    const SCENERIO_UPDATE_SCHOOL_PASSWORD = 'update_password';
    const SCENERIO_SETTINGS_DELETE_ACCOUNT = 'settings_delete_acount';
 
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }
 
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }
 
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],



            //[['type', 'auth_key', 'password_hash', 'created_at', 'updated_at'], 'required'],
            [['password_hash','auth_key','type'], 'required'],
            [['type', 'subscription_plan', 'oauth_provider'], 'string'],
            [['class', 'status', 'created_at', 'updated_at'], 'integer'],
            [['subscription_expiry', 'token_expires', 'last_accessed'], 'safe'],
            [['username', 'image', 'password_hash', 'password_reset_token', 'email', 'verification_token', 'token'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 20],
            [['firstname', 'lastname'], 'string', 'max' => 50],
            [['phone'], 'string', 'max' => 15],
            [['auth_key'], 'string', 'max' => 32],
            [['oauth_uid'], 'string', 'max' => 100],
            [['email'], 'unique'],
            [['username'], 'unique'],
            [['password_reset_token'], 'unique'],
            [['email'], 'required','on' => self::SCENERIO_UPDATE_SCHOOL_EMAIL],
            [['password_hash'], 'required','on' => self::SCENERIO_UPDATE_SCHOOL_PASSWORD],
            [['status','auth_key','email','phone','',''], 'required','on' => self::SCENERIO_SETTINGS_DELETE_ACCOUNT],
        ];
    }
 
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }
 
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token]);
        //throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }
 
    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByLoginDetail($userEmail)
    {   
        return User::find()
                ->where([
                    'and',
                    ['status' => self::STATUS_ACTIVE],
                    ['or', ['email' => $userEmail], ['phone' => $userEmail],['code' => $userEmail]]
                ])->one();
    }
 
    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }
 
    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }
 
    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
 
    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        $checkIfPasswordCorrect =  Yii::$app->security->validatePassword($password, $this->password_hash);
        return $checkIfPasswordCorrect;
    }
 
    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }
 
    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();

        return $this->auth_key;
    }

    public function getRateLimit($request, $action)
    {
        return [$this->rateLimit, 1]; // $rateLimit requests per second
    }

    public function loadAllowance($request, $action)
    {
        return [$this->allowance, $this->allowance_updated_at];
    }

    public function saveAllowance($request, $action, $allowance, $timestamp)
    {
        $this->allowance = $allowance;
        $this->allowance_updated_at = $timestamp;
        $this->save();
    }

}