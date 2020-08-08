<?php

namespace app\modules\v2\components;

use app\modules\v2\models\GlobalClass;
use app\modules\v2\models\SchoolAdmin;
use app\modules\v2\models\Schools;
use app\modules\v2\models\TeacherClass;
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

}