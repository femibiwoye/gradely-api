<?php

namespace app\modules\v2\models;


use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "user".
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
 * @property string|null $last_accessed Last time the website was accessed
 * @property int $is_boarded
 *
 * @property Catchup[] $catchups
 * @property ClassAttendance[] $classAttendances
 * @property HomeworkQuestions[] $homeworkQuestions
 * @property Parents[] $parents
 * @property Parents[] $parents0
 * @property QuizSummary[] $quizSummaries
 * @property SchoolAdmin[] $schoolAdmins
 * @property SchoolTeachers[] $schoolTeachers
 * @property Schools[] $schools
 * @property SecurityQuestionAnswer[] $securityQuestionAnswers
 * @property StudentSchool[] $studentSchools
 * @property TeacherClass[] $teacherClasses
 * @property TeacherClassSubjects[] $teacherClassSubjects
 * @property UserPreference[] $userPreferences
 * @property UserProfile[] $userProfiles
 */
class UserModel extends User
{

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'auth_key', 'password_hash', 'created_at', 'updated_at'], 'required'],
            [['type', 'subscription_plan', 'oauth_provider'], 'string'],
            [['class', 'status', 'created_at', 'updated_at', 'is_boarded'], 'integer'],
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'code' => 'Code',
            'firstname' => 'Firstname',
            'lastname' => 'Lastname',
            'phone' => 'Phone',
            'image' => 'Image',
            'type' => 'Type',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'email' => 'Email',
            'class' => 'Class',
            'status' => 'Status',
            'subscription_expiry' => 'Subscription Expiry',
            'subscription_plan' => 'Subscription Plan',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'verification_token' => 'Verification Token',
            'oauth_provider' => 'Oauth Provider',
            'token' => 'Token',
            'token_expires' => 'Token Expires',
            'oauth_uid' => 'Oauth Uid',
            'last_accessed' => 'Last Accessed',
            'is_boarded' => 'Is Boarded',
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        if ($this->isRelationPopulated('parentChildren'))
            $fields['parentChildren'] = 'parentChildren';

        return $fields;
    }


    /**
     * Gets query for [[Catchups]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCatchups()
    {
        return $this->hasMany(Catchup::className(), ['student_id' => 'id']);
    }

    /**
     * Gets query for [[ClassAttendances]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClassAttendances()
    {
        return $this->hasMany(ClassAttendance::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[HomeworkQuestions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHomeworkQuestions()
    {
        return $this->hasMany(HomeworkQuestions::className(), ['teacher_id' => 'id']);
    }

    /**
     * Gets query for [[Parents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParents()
    {
        return $this->hasMany(Parents::className(), ['parent_id' => 'id']);
    }

    /**
     * Gets query for [[Parents0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParentStudent()
    {
        return $this->hasMany(Parents::className(), ['student_id' => 'id']);
    }

    /**
     * Gets query for [[QuizSummaries]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuizSummaries()
    {
        return $this->hasMany(QuizSummary::className(), ['student_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolAdmins]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolAdmins()
    {
        return $this->hasMany(SchoolAdmin::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolTeachers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolTeachers()
    {
        return $this->hasMany(SchoolTeachers::className(), ['teacher_id' => 'id']);
    }

    /**
     * Gets query for [[Schools]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchools()
    {
        return $this->hasMany(Schools::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[SecurityQuestionAnswers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSecurityQuestionAnswers()
    {
        return $this->hasMany(SecurityQuestionAnswer::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[StudentSchools]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudentSchools()
    {
        return $this->hasMany(StudentSchool::className(), ['student_id' => 'id']);
    }

    /**
     * Gets query for [[TeacherClasses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeacherClasses()
    {
        return $this->hasMany(TeacherClass::className(), ['teacher_id' => 'id']);
    }

    /**
     * Gets query for [[TeacherClassSubjects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeacherClassSubjects()
    {
        return $this->hasMany(TeacherClassSubjects::className(), ['teacher_id' => 'id']);
    }

    /**
     * Gets query for [[UserPreferences]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserPreferences()
    {
        return $this->hasMany(UserPreference::className(), ['user_id' => 'id']);
    }

    /**
     * Gets query for [[UserProfiles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserProfiles()
    {
        return $this->hasMany(UserProfile::className(), ['user_id' => 'id']);
    }

    /**
     * Return children of a parent
     */
    public function getParentChildren()
    {
        return $this->hasMany(self::className(), ['id' => 'student_id'])->via('parentLists');
    }

    public function getParentLists()
    {
        return $this->hasMany(Parents::className(), ['parent_id' => 'id']);
    }


}
