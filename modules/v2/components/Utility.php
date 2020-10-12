<?php

namespace app\modules\v2\components;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\GlobalClass;
use app\modules\v2\models\Parents;
use app\modules\v2\models\SchoolAdmin;
use app\modules\v2\models\Schools;
use app\modules\v2\models\{TeacherClass, Classes, StudentSchool, SchoolTeachers};

use app\modules\v2\models\User;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;


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
        $classes = ArrayHelper::getColumn(TeacherClass::find()->where(['teacher_id' => $teacherID])->all(), 'class_id');
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
            if (!isset($_GET['child_id']) || empty($_GET['child_id']))
                return null;
            $child_id = $_GET['child_id'];
            if (Parents::find()->where(['parent_id' => Yii::$app->user->id, 'student_id' => $child_id, 'status' => 1])->exists())
                return $child_id;
        } elseif (Yii::$app->user->identity->type == 'student')
            return Yii::$app->user->id;

        return null;
    }

    public static function getStudentClass($global_id = SharedConstant::VALUE_ZERO, $studentID = null)
    {
        if (!empty($studentID)) {
            $user = User::findOne(['id' => $studentID, 'type' => 'student']);
            $data = StudentSchool::findOne(['student_id' => $studentID]);

            if (empty($data)) {
                if (!empty($user))
                    return $user->class;
                return SharedConstant::VALUE_NULL;
            } elseif ($global_id == SharedConstant::VALUE_ONE) {
                return $data->class->global_class_id;
            } else {
                return $data->class_id;
            }
        }


        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return null;
        }

        $data = StudentSchool::findOne(['student_id' => Yii::$app->user->id]);

        if (empty($data)) {
            if (!empty(Yii::$app->user->identity->class))
                return Yii::$app->user->identity->class;
            return SharedConstant::VALUE_NULL;
        } elseif ($global_id == SharedConstant::VALUE_ONE) {
            return $data->class->global_class_id;
        } else {
            return $data->class_id;
        }
    }

    /**
     * If student class category.
     * Student could either be in primary or secondary
     * @param $class_id
     * @return string|null
     */
    public static function getStudentClassCategory($class_id)
    {
        if ($class_id >= 7 && $class_id <= 12)
            $category = 'secondary';
        elseif ($class_id >= 1 && $class_id <= 6 || $class_id > 12)
            $category = 'primary';
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
    public static function getStudentTermWeek($only = null)
    {
        $school_id = StudentSchool::find()
            ->select(['school_id', 'class_id'])
            ->where(['student_id' => Yii::$app->user->id])
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

        return ['term' => strtolower($term), 'week' => strtolower($week)];
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
            $image = "https://res.cloudinary.com/gradely/image/upload/v1600773596/placeholders/$folder.png";
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

    public static function getTeacherSchoolID($teacher_id)
    {
        $model = SchoolTeachers::findOne(['teacher_id' => $teacher_id,'status'=>1]);
        if (!$model) {
            return false;
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

    public static function ImageQuery($name)
    {
        return "IF($name.image IS NULL or $name.image = '', 'https://res.cloudinary.com/gradely/image/upload/v1600773596/placeholders/topics.png',IF($name.image LIKE '%http%',$name.image, CONCAT('https://gradely.ng/images/topics/',$name.image))) as image";
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
        if ($classes = StudentSchool::findOne(['student_id' => $user->id, 'status' => 1])) {
            $className = $classes->class->class_name;
            $schoolName = $classes->school->name;
            $hasSchool = true;
        } else {
            $className = null;
            $schoolName = null;
            $hasSchool = false;
        }

        return $return = [
            'profileClass' => $user->class,
            'class_name' => $className,
            'school_name' => $schoolName,
            'has_school' => $hasSchool
        ];
    }
}