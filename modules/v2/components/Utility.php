<?php

namespace app\modules\v2\components;

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
    public static function getSchoolAccess()
    {

        $schools = Schools::find()
            ->select(['user_id', 'id'])
            ->where(['user_id' => Yii::$app->user->id])
            ->all();

        $schoolAdmin = SchoolAdmin::findAll(['user_id' => Yii::$app->user->id, 'status' => 1]);

        $schools = ArrayHelper::merge(ArrayHelper::getColumn($schools, 'id'), ArrayHelper::getColumn($schoolAdmin, 'school_id'));

        return array_unique($schools);
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

}