<?php

namespace app\modules\v2\components;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\exam\StudentExamConfig;
use app\modules\v2\models\GlobalClass;
use app\modules\v2\models\Parents;
use app\modules\v2\models\SchoolAdmin;
use app\modules\v2\models\SchoolCurriculum;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentSummerSchool;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\{SchoolTopic,
    TeacherClass,
    Classes,
    StudentSchool,
    SchoolTeachers,
    QuizSummary,
    QuizSummaryDetails,
    SubjectTopics,
    VideoContent,
    Recommendations,
    RecommendationTopics
};

use app\modules\v2\models\User;
use Aws\Credentials\Credentials;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\db\Expression;


class Utility extends ActiveRecord
{

    /**
     * Created this function to enable multiple school access.
     * ID of the schools i have access to will be returned as an array.
     *
     * @return array
     */
    public static function getSchoolAccess($userID = null)
    {

        if (empty($userID))
            $userID = Yii::$app->user->id;

        $schools = Schools::find()
            ->select(['user_id', 'id'])
            ->where(['user_id' => $userID])
            ->all();

        $schoolAdmin = SchoolAdmin::findAll(['user_id' => $userID, 'status' => 1]);
        $schools = ArrayHelper::merge(ArrayHelper::getColumn($schools, 'id'), ArrayHelper::getColumn($schoolAdmin, 'school_id'));
        return array_unique($schools);
    }

    public static function allSchoolUserID($schoolID)
    {
        $userID = Yii::$app->user->id;
        $schoolAdmin = SchoolAdmin::find()->where(['school_id' => $schoolID, 'status' => 1])->all();

        return ArrayHelper::merge(ArrayHelper::getColumn($schoolAdmin, 'user_id'), [$userID]);

    }

    public static function getSchoolRole(Schools $school)
    {
        if ($school->user_id == Yii::$app->user->id)
            return 'owner';

        $model = SchoolAdmin::find()->where(['user_id' => Yii::$app->user->id, 'school_id' => $school->id]);
        if ($model->exists())
            return $model->one()->level;
        return null;
    }

    /**
     *
     * This return IDs of classes teacher belongs to.
     *
     * @param $teacherID
     * @return array
     */
    public static function getTeacherClassesID($teacherID)
    {
        $classes = ArrayHelper::getColumn(TeacherClass::find()->where(['teacher_id' => $teacherID, 'status' => 1])->all(), 'class_id');
        return $classes;
    }

    public static function getGlobalClasses($classID, $school)
    {
        $fullName = GlobalClass::findOne(['id' => $classID])->description;
        if ($school->naming_format == 'ss') {
            if ($classID <= 6) {
                $fullName = 'Primary ';
            } elseif ($classID >= 7 && $classID <= 9) {
                $fullName = 'Junior secondary school ';
            } elseif ($classID >= 9 && $classID <= 12) {
                $fullName = 'Senior secondary school ';
            }

            switch ($classID) {
                case ($classID == 7 || $classID == 10):
                    $fullName = $fullName . '1';
                    break;
                case  ($classID == 8 || $classID == 11):
                    $fullName = $fullName . '2';
                    break;
                case  ($classID == 9 || $classID == 12):
                    $fullName = $fullName . '3';
                    break;
                case  ($classID > 12):
                    $fullName;
                    break;
                default:
                    $fullName = $fullName . $classID;
                    break;
            }
        } else if ($school->naming_format == 'montessori') {
///0-3 years = Infant/Toddler
//3-6 years = Primary/Children's House
//6-9 years = Lower Elementary
//9-12 years = Upper Elementary

            if ($classID > 12) {
                $fullName = 'Infant/Toddler';
            } elseif ($classID >= 1 && $classID <= 3) {
                $fullName = "Lower Elementary";
            } elseif ($classID >= 4 && $classID <= 6) {
                $fullName = "Upper Elementary";
            } elseif ($classID >= 7 && $classID <= 9) {
                $fullName = 'Lower Secondary';
            } elseif ($classID >= 9 && $classID <= 12) {
                $fullName = 'Upper Secondary';
            }
        } else {
            if ($classID <= 12) $fullName = 'Year ' . $classID;
        }

        return [
            'id' => $classID,
            'name' => $fullName
        ];
    }

    public static function getMyGlobalClassesID($type)
    {
        if ($type == 'primary')
            $classes = GlobalClass::find()->where(['between', 'id', 1, 6])->orWhere(['>', 'id', 12])->andWhere(['status' => 1])->all();
        elseif ($type == 'secondary')
            $classes = GlobalClass::find()->where(['between', 'id', 7, 12])->andWhere(['status' => 1])->all();
        else
            $classes = GlobalClass::find()->andWhere(['status' => 1])->all();

        return $classes;
    }

    public static function getSchoolAdditionalData($userID)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess($userID)]);
        $school_owner = $school->user_id == $userID ? 1 : 0;

        $role = 'None';
        if ($school->user_id == $userID)
            $role = 'Owner';
        elseif ($schoolAdmin = SchoolAdmin::findOne(['user_id' => $userID, 'status' => 1]))
            $role = $schoolAdmin->role->title;

        return [
            'school_id' => $school->id,
            'state' => $school->state,
            'country' => $school->country,
            'school_name' => $school->name,
            'school_slug' => $school->slug,
            'school_owner' => $school_owner,
            'role' => $role
        ];
    }

    public static function getTeacherAdditionalData($userID)
    {

        return [
            'has_class' => TeacherClass::find()->where(['teacher_id' => $userID, 'status' => 1])->exists() ? 1 : 0
        ];
    }


    public static function getParentChildID()
    {
        if (Yii::$app->user->identity->type == 'parent') {
            if ((!isset($_GET['child_id']) || empty($_GET['child_id'])) && empty($_GET['child']))
                return 0;
            $child_id = isset($_GET['child_id']) ? $_GET['child_id'] : $_GET['child'];
            if (Parents::find()->where(['parent_id' => Yii::$app->user->id, 'student_id' => $child_id, 'status' => 1])->exists())
                return $child_id;
        } elseif (Yii::$app->user->identity->type == 'student')
            return Yii::$app->user->id;

        return null;
    }

    public static function getChildParentIDs($studentID = null, $parentID = null)
    {
        $studentID = !empty($studentID) ? $studentID : Yii::$app->user->id;
        if (empty($parentID))
            $model = Parents::find()->select(['parent_id'])->where(['student_id' => $studentID, 'status' => 1])->all();
        elseif (!empty($parentID))
            $model = Parents::find()->select(['parent_id'])->where(['student_id' => $studentID, 'parent_id' => $parentID, 'status' => 1])->all();

        return ArrayHelper::getColumn($model, 'parent_id');
    }

    public static function getChildMode($studentID)
    {
        if (Yii::$app->user->identity->type == 'parent') {
            return ArrayHelper::getValue(User::findOne(['id' => Yii::$app->user->id]), 'mode', 'practice');
        }
        return ArrayHelper::getValue(User::findOne(['id' => $studentID]), 'mode', 'practice');
    }

    /**
     * This function allows you to get the class of a student.
     * There are 2 options. global_id is 0 when you want to get school class, e.g 1839 could be student class_id while in JSS1
     * and global_id is 1 when you want to get the global class_id. e.g 7 is JSS 1
     * @param int $global_id
     * @param null $studentID
     * @return int|mixed|string|null
     */
    public static function getStudentClass($global_id = SharedConstant::VALUE_ZERO, $studentID = null, $classFullname = false)
    {
        if (!empty($studentID)) {
            $user = User::findOne(['id' => $studentID, 'type' => 'student']);
            $data = StudentSchool::findOne(['student_id' => $studentID, 'status' => 1, 'is_active_class' => 1]);

            if (empty($data)) {
                if (!empty($user)) {
                    return $classFullname ? GlobalClass::findOne(['id' => $user->class]) : $user->class;
                }
                return SharedConstant::VALUE_NULL;
            } elseif ($global_id == SharedConstant::VALUE_ONE && isset($data->class->global_class_id)) {
                return $data->class->global_class_id;
            } elseif ($classFullname) {
                return Classes::findOne(['id' => $data->class_id]);
            } else {
                return $data->class_id;
            }
        }


        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return null;
        }

        $data = StudentSchool::findOne(['student_id' => Yii::$app->user->id, 'is_active_class' => 1, 'status' => 1]);
        if (empty($data)) {
            if (!empty(Yii::$app->user->identity->class))
                return Yii::$app->user->identity->class;
            return SharedConstant::VALUE_NULL;
        } elseif ($global_id == SharedConstant::VALUE_ONE) {
            return isset($data->class->global_class_id) ? $data->class->global_class_id : Yii::$app->user->identity->class;
        } else {
            return !empty($data->class_id) ? $data->class_id : Yii::$app->user->identity->class;
        }
    }

    /**
     * If student class category.
     * Student could either be in primary or secondary
     * @param $class_id
     * @return string[]|null
     */
    public static function getStudentClassCategory($class_id)
    {
        if ($class_id >= 7 && $class_id <= 9)
            $category = ['secondary', 'junior', 'primary-junior'];
        elseif ($class_id >= 10 && $class_id <= 12)
            $category = ['senior', 'secondary'];
        elseif ($class_id >= 1 && $class_id <= 6 || $class_id > 12)
            $category = ['primary', 'primary-junior'];
        else
            $category = null;
        return $category;
    }

    /**
     * @param $category
     * @return string[]|null
     */
    public static function getCategoryChildren($category)
    {
        if ($category == 'primary')
            $category = ['primary', 'primary-junior'];
        elseif ($category == 'secondary')
            $category = ['primary-junior', 'junior', 'senior', 'secondary'];
        else
            $category = null;
        return $category;
    }


    /**
     * Child subscription status
     * @param null $student
     * @param null $value
     * @return bool
     */
    public static function getSubscriptionStatus($student = null, $value = null)
    {
        if (empty($student)) {
            $student = Yii::$app->user->identity;
        }
        if ($value == 'plan') {
            return $value = $student->subscription_plan;
        } else {
            $expiry = $student->subscription_expiry;
            return $value = $expiry != null && strtotime($expiry) > time();
        }
    }

    /**
     * This return current week and term of a student.
     * @return array
     * @throws \Exception
     */
    public static function getStudentTermWeek($only = null, $studentID = null)
    {
        if (empty($studentID))
            $studentID = Yii::$app->user->id;
        $school_id = StudentSchool::find()
            ->select(['school_id', 'class_id'])
            ->where(['student_id' => $studentID, 'status' => 1, 'is_active_class' => 1])
            ->asArray()
            ->one();

        if (!$school_id) {
            $term = SessionTermOnly::widget(['nonSchool' => true]);
            $week = SessionTermOnly::widget(['nonSchool' => true, 'weekOnly' => true]);
        } else {
            $term = SessionTermOnly::widget(['id' => $school_id['school_id']]);
            $week = SessionTermOnly::widget(['id' => $school_id['school_id'], 'weekOnly' => true]);
        }
        if (!empty($only)) {
            if ($only == 'week')
                return strtolower($week);
            elseif ($only == 'term')
                return strtolower($term);

        }

        return ['term' => strtolower($term), 'week' => strtolower($week), 'session' => '2021-2022'];
    }

    /**
     * This manages image url.
     * @param $image
     * @param $folder
     * @return string
     */
    public static function AbsoluteImage($image, $folder)
    {
        if (empty($image) && !empty($folder))
            $image = "https://gradly.s3.eu-west-2.amazonaws.com/placeholders/$folder.png";
        elseif (strpos($image, 'http') !== false)
            $image = $image;
        else {
            $image = Yii::$app->params['baseURl'] . "/images/$folder/" . $image;
        }
        return $image;
    }

    public static function ProfileImage($image)
    {
        if (empty($image))
            $image = null;
        elseif (strpos($image, 'http') !== false)
            $image = $image;
        else {
            $image = Yii::$app->params['baseURl'] . '/images/users/' . $image;
        }
        return $image;

    }

    public static function getGeneralImage($image, $folder)
    {
        if (empty($image))
            $image = null;
        elseif (strpos($image, 'http') !== false)
            $image = $image;
        else {
            $image = Yii::$app->params['baseURl'] . "/images/$folder/" . $image;
        }
        return $image;
    }

    public static function getTeacherSchoolID($teacher_id, $failReturn = false, $class_id = null)
    {
        if (!empty($class_id)) {
            $model = TeacherClass::findOne(['teacher_id' => $teacher_id, 'status' => 1, 'class_id' => $class_id]);
        } else {
            $model = SchoolTeachers::findOne(['teacher_id' => $teacher_id, 'status' => 1]);
        }
        if (!$model) {
            return $failReturn;
        }

        return $model->school_id;
    }

    public static function GetVideo($contentID)
    {
        $url = Yii::$app->params['videoDomain'] . "?content_id=$contentID&user_id=" . Yii::$app->user->id;
        $curl = curl_init("$url");

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31'
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . Yii::$app->params['wizItUpKey'],
            'Content-Type: application/json',
            "Cache-Control: no-cache",
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }

    public static function ImageQuery($name, $folder = 'topics')
    {
        if ($folder == 'users')
            return "IF($name.image LIKE '%http%',$name.image, CONCAT('https://live.gradely.ng/images/$folder/',$name.image)) as image";
        else
            return "IF($name.image IS NULL or $name.image = '', 'https://gradly.s3.eu-west-2.amazonaws.com/placeholders/topic.png',IF($name.image LIKE '%http%',$name.image, CONCAT('https://live.gradely.ng/images/$folder/',$name.image))) as image";
    }

    public static function ThumbnailQuery($name, $type)
    {
        return "IF($name.thumbnail IS NULL or $name.thumbnail = '', 'https://gradly.s3.eu-west-2.amazonaws.com/placeholders/$type.png',$name.thumbnail) as thumbnail";
    }

    public static function StudentClassDetails($child = null)
    {
        $user = Yii::$app->user->identity;
        if ($user->type == 'parent' && $child) {
            $parent = Parents::findOne(['parent_id' => $user->id, 'student_id' => $child, 'status' => 1]);
            if ($parent)
                $user = User::findOne(['id' => $child]);
            else
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid');

        }

        $summerSchool = StudentSummerSchool::find()
            ->alias('sss')
            ->select([
                'sss.id as summer_id',
                'sss.school_id as school_id',
                'sss.class_id as class_id',
                'classes.class_name',
                'schools.name as school_name',
                'sss.subjects as subjects',
            ])
            ->innerJoin('classes', 'classes.id = sss.class_id')
            ->innerJoin('schools', 'schools.id = classes.school_id')
            ->where(['student_id' => $user->id])->asArray()->one();

        if ($classes = StudentSchool::findOne(['student_id' => $user->id, 'status' => 1, 'is_active_class' => 1])) {

            if (empty($classes->class)) {
                $classId = null;
                $className = null;
                $schoolName = null;
                $hasSchool = false;
                $in_summer_school = null;
                $alternative_school = $summerSchool;
            } else {

                if ($classes->in_summer_school == 1) {
                    if (!empty($summerSchool)) {
                        $classId = (int)$summerSchool['class_id'];
                        $className = $summerSchool['class_name'];
                        $schoolName = $summerSchool['school_name'];
                        $hasSchool = true;
                    } else {
                        $classId = null;
                        $className = null;
                        $schoolName = null;
                        $hasSchool = false;

                        $summerSchool = ['class_id' => $classId, 'class_name' => $className, 'school_name' => $schoolName];
                    }
                    $in_summer_school = $classes->in_summer_school;
                    $alternative_school = array_merge($summerSchool, ['class_name' => $classes->class->class_name, 'school_name' => $classes->school->name, 'class_id' => $classes->class_id]);
                } else {
                    $classId = $classes->class_id;
                    $className = $classes->class->class_name;
                    $schoolName = $classes->school->name;
                    $hasSchool = true;
                    $in_summer_school = empty($summerSchool) ? null : $classes->in_summer_school;
                    $alternative_school = $summerSchool;
                }
            }
        } else {
            $classId = null;
            $className = null;
            $schoolName = null;
            $hasSchool = false;
            $in_summer_school = !empty($summerSchool) ? 0 : null;
            $alternative_school = $summerSchool;
        }

        return $return = [
            'profileClass' => $user->class,
            'class_id' => $classId,
            'class_name' => $className,
            'school_name' => $schoolName,
            'has_school' => $hasSchool,
            'in_summer_school' => $in_summer_school,
            'alternative_school' => $alternative_school
        ];
    }

    public static function GetStudentSummerSchoolStatus($studentID)
    {
        if ($student = StudentSchool::findOne(['student_id' => $studentID, 'status' => 1, 'is_active_class' => 1, 'current_class' => 1])) {
            return $student->in_summer_school;
        } else {
            return null;
        }

        /**
         * null = Not in summer school and not in real school
         * 0 = you are in real school, you've been in summer school before and not active
         * 1 = you are in summer school
         */
    }

    /**
     * THis return the id of the student school
     * @param null $child
     * @return StudentSchool|null
     */
    public static function StudentSchoolId($child = null)
    {
        $model = StudentSchool::findOne(['student_id' => $child, 'status' => 1, 'is_active_class' => 1, 'current_class' => 1]);
        return !empty($model) ? $model : null;
    }

    public function generateRecommendation($quiz_id)
    {
        try {
            if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
            }

            $quizSummary = QuizSummary::find()->where([
                'id' => $quiz_id, 'submit' => 1,
                'student_id' => Yii::$app->user->id
            ])->one();

            if (!$quizSummary)
                return false;


            //$topics retrieves low scoring topic_ids
            $topics = QuizSummaryDetails::find()
                ->alias('qsd')
                ->select([
                    new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                    'qsd.topic_id',
                ])
                ->where([
                    'qsd.student_id' => Yii::$app->user->id,
                    'homework_id' => $quizSummary->homework_id
                ])
                ->orderBy(['score' => SORT_ASC])
                ->asArray()
                ->limit(SharedConstant::VALUE_TWO)
                ->groupBy('qsd.topic_id')
                ->all();

            //$topic_objects retrieves topic objects
            $topic_objects = SubjectTopics::find()
                ->select([
                    'subject_topics.*',
                    new Expression("'practice' as type")
                ])
                ->where(['id' => ArrayHelper::getColumn($topics, 'topic_id')])
                ->asArray()
                ->all();

            $this->addRecommendations($topic_objects, $quizSummary->childHomework, $quizSummary->subject_id);

            //retrieves assign videos to the topic
            $video_objects = VideoContent::find()
                ->select([
                    'video_content.*',
                    new Expression("'video' as type")
                ])
                ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
                ->where(['video_assign.topic_id' => ArrayHelper::getColumn($topics, 'topic_id')])
                ->limit(SharedConstant::VALUE_ONE)
                ->asArray()
                ->all();

            if (!$topic_objects and !$video_objects) {
                return false;
            }

            $this->addRecommendations($video_objects, $quizSummary->childHomework, $quizSummary->subject_id);

            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    private function addRecommendations($recommendations, $homework, $subject)
    {
        $model = new Recommendations;
        $model->student_id = Yii::$app->user->id;
        $model->category = $homework->type;
        $model->reference_id = $homework->id;
        $model->reference_type = $homework->reference_type;

        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()) {
                return false;
            }

            if (!$this->addRecommendationTopics($recommendations, $model->id, $subject)) {
                return false;
            }

            $dbtransaction->commit();
        } catch (Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }
    }

    private function addRecommendationTopics($topics, $recommendation_id, $subject)
    {
        foreach ($topics as $topic) {
            $model = new RecommendationTopics();
            $model->recommendation_id = $recommendation_id;
            $model->student_id = Yii::$app->user->id;
            $model->object_id = $topic['id'];
            $model->object_type = $topic['type'];
            if ($topic['type'] == 'video') {
                $model->subject_id = $subject;
            } else {
                $model->subject_id = $topic['subject_id'];

            }
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }

    public static function UserLocation()
    {
        $ip = '41.217.70.122';//$_SERVER['REMOTE_ADDR']; // get client's IP
        return $details = json_decode(file_get_contents("http://ipinfo.io/{$ip}/json"));
    }

    public static function FormatBytesSize($bytes, $precision = 2)
    {
        $units = array('b', 'kb', 'mb', 'gb', 'tb');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        // $bytes /= pow(1024, $pow);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . $units[$pow];
    }

    public static function ParentStudentChildClass($child_id = null, $globalIDStatus = 1)
    {
        if (Yii::$app->user->identity->type == 'parent' && Parents::find()->where(['student_id' => $child_id, 'parent_id' => Yii::$app->user->id, 'status' => 1])->exists()) {
            $class_id = Utility::getStudentClass($globalIDStatus, $child_id);
        } else
            $class_id = Utility::getStudentClass($globalIDStatus);

        return $class_id;
    }

    /**
     * This is used to validate student relationship and check class.
     * Parent -> Student
     * School -> Class -> Student
     * Teacher -> Class -> Student
     * Student
     *
     * @param null $child_id
     * @param int $globalIDStatus
     * @return int|mixed|string|null
     */
    public static function StudentChildClass($child_id = null, $globalIDStatus = 1)
    {
        $type = Yii::$app->user->identity->type;
        if ($type == 'parent' && Parents::find()->where(['student_id' => $child_id, 'parent_id' => Yii::$app->user->id, 'status' => 1])->exists()) {
            $class_id = Utility::getStudentClass($globalIDStatus, $child_id);
        } elseif ($type == 'teacher' && StudentSchool::find()->where(['student_id' => !empty($child_id) ? $child_id : Yii::$app->request->get('id'), 'school_id' => Utility::getTeacherSchoolID(Yii::$app->user->id)])->exists()) {
            $class_id = Utility::getStudentClass($globalIDStatus, !empty($child_id) ? $child_id : Yii::$app->request->get('id'));
        } elseif ($type == 'school' && StudentSchool::find()->where(['student_id' => $child_id, 'school_id' => Utility::getSchoolAccess(Yii::$app->user->id)])->exists()) {
            $class_id = Utility::getStudentClass($globalIDStatus, $child_id);
        } else
            $class_id = Utility::getStudentClass($globalIDStatus);

        return $class_id;
    }

    public static function GetNextPreviousTerm($term)
    {
        switch ($term) {
            case 'first':
                return 'second';
                break;
            case 'second':
                return 'third';
                break;
            case 'third';
                return 'first';
                break;
            default:
                return 'first';
        }
    }

    public static function StudentRecommendedTodayStatus($studentID = null)
    {
        if (!$studentID) {
            $studentID = Yii::$app->user->id;
        }
        return Recommendations::find()
            ->where([
                'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ONE],
                'DATE(created_at)' => date('Y-m-d'),
                'student_id' => $studentID
            ])
            ->andWhere('DAY(CURDATE()) = DAY(created_at)')
            ->exists();
    }

    /**
     * This function allows you to create a folder with the necessary permission if it doesn't exist.
     * @param $folder
     * @return bool
     */
    public static function CreateFolder($folder)
    {
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }
        return true;
    }


    /**
     * This deletes folder
     * @param $folder
     * @return bool
     */
    public static function DeleteFolderWithFiles($folder)
    {
        if (is_dir($folder)) {
            array_map('unlink', glob("$folder/*.*"));
            rmdir($folder);
            return true;
        }
    }

    public static function AwsS3Config()
    {
        $credentials = new Credentials(Yii::$app->params['AwsS3Key'], Yii::$app->params['AwsS3Secret']);

        return [
            'version' => 'latest',
            'region' => 'eu-west-2',
            'credentials' => $credentials
        ];
    }

    public static function SchoolStudentSubscriptionDetails($school)
    {
        $basicUsed = (int)StudentSchool::find()->where(['school_id' => $school->id, 'status' => 1, 'subscription_status' => 'basic', 'is_active_class' => 1])->count();
        $premiumUsed = (int)StudentSchool::find()->where(['school_id' => $school->id, 'status' => 1, 'subscription_status' => 'premium', 'is_active_class' => 1])->count();
        //$basicUsed += $premiumUsed;
        return [
            'basic' => ['total' => $school->basic_subscription, 'used' => $basicUsed, 'remaining' => $school->basic_subscription - $basicUsed],
            'premium' => ['total' => $school->premium_subscription, 'used' => $premiumUsed, 'remaining' => $school->premium_subscription - $premiumUsed]
        ];
    }

    /**
     * Remove unnecessary question fields
     * @param $model
     * @return mixed
     */
    public static function FilterQuestionReturns($model)
    {
        if ($model['type'] == 'essay') {
            unset($model['option_a']);
            unset($model['option_b']);
            unset($model['option_c']);
            unset($model['option_d']);
            unset($model['option_e']);
            unset($model['answer']);
            unset($model['score']);
        } elseif ($model['type'] == 'short') {
            unset($model['option_a']);
            unset($model['option_b']);
            unset($model['option_c']);
            unset($model['option_d']);
            unset($model['option_e']);
            unset($model['file_upload']);
            unset($model['word_limit']);
            unset($model['score']);
            $model['answer'] = json_decode($model['answer']);
        }
        return $model;
    }

    /**
     * Status determine of it should return the actual value of the curriculum or status of custom curriculum
     *
     * @param $schoolID
     * @param bool $statusOnly
     * @return bool|int
     */
    public static function SchoolActiveCurriculum($schoolID, $statusOnly = false)
    {
        if ($statusOnly) {
            return SchoolCurriculum::find()->leftJoin('exam_type et', 'et.id = school_curriculum.curriculum_id')->where(['et.school_id' => $schoolID])->exists();
        }
        if ($curriculum = SchoolCurriculum::findOne(['school_id' => $schoolID])) {
            return $statusOnly ? true : $curriculum->curriculum_id;
        }
        return $statusOnly ? false : 1;
    }

    public static function SchoolAlternativeClass($id, $global = false, $schoolID = null)
    {
        if ($global) {
            return GlobalClass::findOne(['id' => $id])->id;
        }
        return ArrayHelper::getColumn(Classes::findAll(['global_class_id' => $id, 'school_id' => $schoolID]), 'id');
    }


    public static function SchoolTopicColumns($curriculumID)
    {
        return [
            '*',
            new Expression('null as slug'),
            new Expression('null as description'),
            new Expression('week as week_number'),
            new Expression("$curriculumID as exam_type_id"),
            new Expression("1 as status"),
            new Expression("null as image"),
        ];
    }

    /**
     * This returns the ID of exams which the student is active in
     * @param $studentID
     * @return array
     */
    public static function StudentExamSubjectID($studentID, $field = 'subject_id')
    {
        return ArrayHelper::getColumn(StudentExamConfig::find()->where(['status' => 1, 'student_id' => $studentID])->select([$field])->groupBy($field)->all(), $field);
    }

    public static function GradeIndicator($grad)
    {
        if ($grad < 65) {
            $grad = 'F';
        } else if ($grad <= 66 && $grad >= 65) {
            $grad = 'D';
        } else if ($grad <= 69 && $grad >= 67) {
            $grad = 'D+';
        } else if ($grad <= 73 && $grad >= 70) {
            $grad = 'C-';
        } else if ($grad <= 76 && $grad >= 74) {
            $grad = 'C';
        } else if ($grad <= 79 && $grad >= 77) {
            $grad = 'C+';
        } else if ($grad <= 83 && $grad >= 80) {
            $grad = 'B-';
        } else if ($grad <= 86 && $grad >= 84) {
            $grad = 'B';
        } else if ($grad <= 89 && $grad >= 87) {
            $grad = 'B+';
        } else if ($grad <= 93 && $grad >= 90) {
            $grad = 'A-';
        } else if ($grad <= 96 && $grad >= 94) {
            $grad = 'A';
        } else if ($grad >= 97) {
            $grad = 'A+';
        }

        return $grad;

    }

    public static function AutoDuplicateTopics($schoolID, $newClassID, $globalClassID, $subjectID)
    {
        if (!SchoolTopic::find()->where(['class_id' => $newClassID, 'school_id' => $schoolID, 'subject_id' => $subjectID])->exists()) {
//            $classObject = Classes::findOne(['id' => $newClassID, 'school_id' => $schoolID]);
            foreach (Classes::find()->where(['global_class_id' => $globalClassID])->all() as $class) {
                if (SchoolTopic::find()->where(['school_id' => $schoolID, 'class_id' => $class->id, 'subject_id' => $subjectID])->exists()) {
                    $topics = SchoolTopic::find()->where(['class_id' => $class->id, 'school_id' => $schoolID, 'subject_id' => $subjectID])->all();
                    foreach ($topics as $topic) {
//                        if (SchoolTopic::find()->where(['class_id' => $newClassID, 'school_id' => $schoolID, 'topic_id' => $topic->topic_id, 'subject_id' => $subjectID])->exists()) {
//                            continue;
//                        }
                        $model = new SchoolTopic();
                        $model->attributes = $topic->attributes;
                        $model->id = null;
                        $model->class_id = $newClassID;
                        $model->save();
                    }
                    break;
                }
            }
        }
    }
}