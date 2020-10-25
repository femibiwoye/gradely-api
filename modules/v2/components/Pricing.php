<?php

namespace app\modules\v2\components;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use yii\base\Widget;
use Yii;
use app\modules\v2\models\{Schools, Options, SchoolTeachers, StudentSchool, Parents};

class Pricing extends Widget
{
    public static function ActivateTrial($id, $type)
    {
        if ($type == 'school') {
            $model = Schools::findOne(['id' => $id]);
            $model->subscription_plan = 'trial';
            $model->subscription_expiry = date("Y-m-d", strtotime("+" . self::SubscriptionTrialValue($type)->value . " days"));
        } else if ($type == 'student') {
            $model = UserModel::findOne(['id' => $id]);
            $model->subscription_plan = 'trial';
            $model->subscription_expiry = date("Y-m-d", strtotime("+" . self::SubscriptionTrialValue($type)->value . " days"));
        }

        if (isset($model) && $model->save()) {
            return true;
        }
        return false;
    }

    private static function SubscriptionTrialValue($type)
    {
        return Options::findOne(['name' => $type . '_trial_day']);
    }

    public static function SubscriptionStatus($schoolID = null, $childID = null, $statusOnly = true)
    {
        try {
            $type = Yii::$app->user->identity->type;
            if ($type == 'school' || $type == 'teacher') {
                if ($type == 'teacher')
                    $schoolID = SchoolTeachers::findOne(['teacher_id' => Yii::$app->user->id, 'status' => 1])->id;
                elseif ($type == 'school')
                    $schoolID = Utility::getSchoolAccess();
                $model = Schools::findOne(['id' => $schoolID]);
                $status = !empty($model->subscription_expiry) && strtotime($model->subscription_expiry) > time() ? true : false;
                $plan = $model->subscription_plan;
                $used_student = StudentSchool::find()->where(['school_id' => $model->id, 'status' => 1])->count();
                $limit = Options::findOne(['name' => $plan . '_students_limit']);
                $unused_student = $limit - $used_student;
                $return = ['status' => $status, 'expiry' => $model->subscription_expiry, 'plan' => $plan, 'used_student' => $used_student, 'unused_student' => $unused_student];

                return $statusOnly ? $status : $return;

            } else if ($type == 'student' || $type == 'parent') {
                if ($type == 'parent' && Parents::find()->where(['status' => 1, 'student_id' => $childID])->exists()) {
                    $studentID = $childID;
                } else if ($type == 'student') {
                    $studentID = Yii::$app->user->id;
                }

                if ($user = UserModel::findOne(['id' => $studentID, 'type' => 'student'])) {
                    $userStatus = !empty($user->subscription_expiry) && strtotime($user->subscription_expiry) > time() ? true : false;
                    $schoolSubStatus = false;
                    $status = true;
                    $is_school = 0;
                    if ($model = StudentSchool::find()->where(['status' => 1, 'student_id' => $childID])->one()) {
                        $model = Schools::findOne(['id' => $schoolID]);
                        $schoolSubStatus = !empty($model->subscription_expiry) && strtotime($model->subscription_expiry) > time() ? true : false;
                        $is_school = $schoolSubStatus ? 1 : 0;
                    }
                    if ($schoolSubStatus || $userStatus) {
                        $status = true;
                    }
                    $return = ['status' => $status, 'expiry' => $user->subscription_expiry, 'plan' => $user->subscription_plan, 'is_school_sub' => $is_school];
                    return $statusOnly ? $status : $return;
                }
            }
        } catch (\Exception $e) {
            $return = ['status' => false, 'expiry' => null, 'plan' => null, 'is_school_sub' => 0];
            return $statusOnly ? false : $return;
        }
    }
}