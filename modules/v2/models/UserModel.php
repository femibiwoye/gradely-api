<?php

namespace app\modules\v2\models;


use app\modules\v2\components\Adaptivity;
use app\modules\v2\components\SmsAuthentication;
use app\modules\v2\components\Utility;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use app\modules\v2\components\SharedConstant;

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
 * @property string|null $mode This is used to know if student is in catchup practice or exam mode
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
            [['username', 'image', 'password_hash', 'password_reset_token', 'email', 'verification_token'], 'string', 'max' => 255],
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
        if ($this->isRelationPopulated('parentChildren')) {
            $fields['parentChildren'] = 'parentChildren';
            //$fields['parentChildrenRelationship'] = 'parentChildrenRelationship';
            //$fields['parentStudentSchools'] = 'parentStudentSchools';
        }

        if ($this->isRelationPopulated('teacherClassesList'))
            $fields['teacherClasses'] = 'teacherClassesList';

        if ($this->isRelationPopulated('teacherFirstClass'))
            $fields['teacherFirstClass'] = 'teacherFirstClass';

        if ($this->isRelationPopulated('teacherSubjectList'))
            $fields['teacherSubjects'] = 'teacherSubjectList';

        //if ($this->isRelationPopulated('assessmentTopicsPerformance')) {
        if ($this->isRelationPopulated('proctor')) {
            $fields['assessmentTopicsPerformance'] = 'assessmentTopicsPerformance';
            $fields['recommendations'] = 'recommendation';
            $fields['quizSummary'] = 'homeworkQuizSummary';
            $fields['quizSummaryScore'] = 'studentScore';
            $fields['proctor'] = 'proctor';
        }

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
        if (isset(Yii::$app->user->identity->type) && Yii::$app->user->identity->type == 'school')
            return $this->hasMany(TeacherClass::className(), ['teacher_id' => 'id'])->andWhere(['status' => 1, 'school_id' => Schools::findOne(['id' => Utility::getSchoolAccess()])->id]);
        else
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
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        return $this->hasMany(self::className(), ['id' => 'student_id'])
            ->leftJoin('parents p', 'p.student_id = user.id AND p.parent_id = ' . $this->id)
            ->innerJoin('student_school s', "s.student_id = user.id AND s.status = 1 AND is_active_class = 1 AND s.school_id = " . $school->id)
            ->leftJoin('classes c', "c.id = s.class_id AND s.status= '1'")
            ->select(['user.id', 'firstname', 'lastname', 'user.code', 'email', Utility::ImageQuery('user', 'users'), 'type', 'p.parent_id', 'p.role', 'c.id class_id', 'c.class_name', 'c.class_code'])
            ->asArray()
            ->via('parentLists');
    }

    public function getParentChildrenRelationship()
    {
        return $this->hasMany(Parents::className(), ['student_id' => 'id'])->andWhere(['parent_id' => $this->id])->via('parentChildren');
    }

    public function getParentStudentSchools()
    {
        return $this->hasMany(StudentSchool::className(), ['student_id' => 'id'])->via('parentChildren');
    }

    public function getParentLists()
    {
        return $this->hasMany(Parents::className(), ['parent_id' => 'id']);
    }

    public function getTeacherClassesList()
    {
        return $this->hasMany(Classes::className(), ['id' => 'class_id'])->via('teacherClasses');
    }

    public function getTeacherFirstClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id'])->via('teacherClasses');
    }

    public function getTeacherSubjects()
    {

        if (isset($_GET['class_id']) && !empty($_GET['class_id']))
            $return = $this->hasMany(TeacherClassSubjects::className(), ['teacher_id' => 'id'])->where(['status' => 1, 'class_id' => $_GET['class_id']]);
        else
            $return = $this->hasMany(TeacherClassSubjects::className(), ['teacher_id' => 'id'])->where(['status' => 1]);


        if (isset(Yii::$app->user->identity->type) && Yii::$app->user->identity->type == 'school') {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            $school_id = $school->id;
            $return = $return->andWhere(['school_id' => $school_id]);
        }elseif (isset($_GET['school_key']) && isset($_GET['school_secret_key'])) {
            $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
            $school_id = $school->id;
            $return = $return->andWhere(['school_id' => $school_id]);
        }

        return $return;
    }

    public function getTeacherSubjectList()
    {
        return $this->hasMany(Subjects::className(), ['id' => 'subject_id'])->via('teacherSubjects');
    }

    public function getAssessmentTopicsPerformance()
    {
        $homeworkID = Yii::$app->request->get('id');
        return $this->hasMany(QuizSummaryDetails::className(), ['student_id' => 'id'])
            ->alias('qsd')
            ->select([
//                new Expression('round((SUM(case when qsd.selected = qsd.answer AND hq.id = '.$homeworkID.' then 1 else 0 end)/COUNT(hq.id))*100) as score'), //To be removed eventually
                new Expression('round((SUM(case when qsd.is_correct = 1 AND hq.id = '.$homeworkID.' then 1 else 0 end)/COUNT(hq.id))*100) as score'),
                'q.topic_id',
                new Expression('(case when (select requester_id from tutor_session where student_id = qsd.student_id AND meta = "recommendation" AND requester_id = ' . Yii::$app->user->id . ' AND status = "pending" AND subject_id = qus.subject_id group by student_id) then 1 else 0 end) as is_recommended'),
                'SUM(case when qsd.selected = q.answer AND qsd.homework_id=hq.homework_id then 1 else 0 end) as correct',
                'COUNT(hq.id) as questionCount',
                'st.topic as topic',
                'hq.homework_id as homework_id',
            ])
            ->innerJoin('homework_questions hq', 'hq.homework_id=qsd.homework_id')
            ->innerJoin('quiz_summary qus', "qus.id = qsd.quiz_id AND qus.type = 'homework'")
            ->innerJoin('questions q', 'q.id=hq.question_id')
            ->innerJoin('subject_topics st', 'st.id=q.topic_id')
            ->where(['hq.homework_id'=>$homeworkID])
            ->groupBy(['q.topic_id'])
            ->orderBy('score ASC')
            ->asArray();
    }

    public function getRecommendation()
    {
        $videos = is_array($this->getVideos($this->remedial)) ? $this->getVideos($this->remedial) : [];
        $practice = is_array($this->getPractice($this->remedial)) ? $this->getPractice($this->remedial) : [];
        $resources = array_merge($videos, $practice);
        shuffle($resources);
        $return = [
            'remedial' => $this->remedial,
            'resources' => $resources,
        ];
        return $this->homeworkQuizSummary->score <= 40 ? $return : [];
    }

    public function getRemedial()
    {
        $least_topics = $this->getAssessmentTopicsPerformance()->all();
        if ($least_topics) {
            $most_least_topic = $least_topics[SharedConstant::VALUE_ZERO];
            foreach ($least_topics as $least_topic) {
                if ($least_topic['score'] < $most_least_topic['score']) {
                    $most_least_topic = $least_topic;
                }
            }

            if ($most_least_topic['score'] > 75) {
                return null;
            }

            return $most_least_topic;
        }


        return SharedConstant::VALUE_NULL;
    }

    public function getVideos($topic = null)
    {
        if (isset($topic['score']) && $topic['score'] >= 75) {
            return null;
        }

        if (empty($topic)) {
            return null;
        }

        $videos = ArrayHelper::getColumn(VideoAssign::find()
            ->where(['topic_id' => $topic['topic_id']])
            ->limit(5)
            ->select('content_id')->all(), 'content_id');
        $classID = Utility::getStudentClass(null, $this->id);
        $referenceID = Yii::$app->request->get('id');
        return VideoContent::find()
            ->select([
                '*', new Expression("'$classID' as class_id"),
                new Expression("'video' as type"),
                new Expression('(case when (select resources_id from recommended_resources where creator_id = ' . Yii::$app->user->id . ' AND resources_type = "video" AND receiver_id = ' . $this->id . ' AND resources_id = video_content.id AND reference_type = "homework" AND reference_id = ' . $referenceID . ') then 1 else 0 end) as is_recommended'),
            ])
            ->where(['id' => $videos])
            ->limit(SharedConstant::VALUE_FIVE)
            ->asArray()
            ->all();
    }

    public function getPractice($least_topic = null)
    {
        $quizSummary = QuizSummary::find()
            ->where([
                'homework_id' => Yii::$app->request->get('id'),
                'submit' => 1
            ])
            ->one();

        if (!$quizSummary)
            return null;


        //$topics retrieves low scoring topic_ids
        $topics = QuizSummaryDetails::find()
            ->alias('qsd')
            ->select([
                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                new Expression('(case when (select requester_id from tutor_session where student_id = qsd.student_id AND meta = "recommendation" AND requester_id = ' . Yii::$app->user->id . ' AND status = "pending" AND subject_id = ' . $quizSummary->subject_id . ' group by student_id) then 1 else 0 end) as is_recommended'),
                'qsd.topic_id'
            ])
            ->where([
                'qsd.homework_id' => $quizSummary->homework_id
            ])
            ->orderBy(['score' => SORT_ASC])
            ->asArray()
            ->limit(SharedConstant::VALUE_FIVE)
            ->groupBy('qsd.topic_id')
            ->all();

        $topic_id = ArrayHelper::getColumn($topics, 'topic_id');
        $practiceVideosRecommendations = Adaptivity::PracticeVideoRecommendation($topic_id, $this->id, 'homework', $quizSummary->homework_id,$quizSummary->subject_id);

        if (!$practiceVideosRecommendations) {
            return null;
        }

        return $practiceVideosRecommendations;
    }


    public function getHomeworkQuizSummary()
    {
        return $this->hasOne(QuizSummary::className(), ['student_id' => 'id'])
            ->andWhere(['homework_id' => Yii::$app->request->get('id'), 'submit' => 1]);
    }

    public function getStudentScore()
    {
        return ArrayHelper::getValue($this->homeworkQuizSummary,'score',null);
    }

    public function getProctor()
    {
        return $this->hasOne(ProctorReport::className(), ['student_id' => 'id'])
            ->andWhere(['assessment_id' => Yii::$app->request->get('id')]);
    }
}
