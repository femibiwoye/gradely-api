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
            $model->subscription_expiry = date("Y-m-d H:i:s", strtotime("+" . self::SubscriptionTrialValue($type)->value . " days"));
        } else if ($type == 'student') {
            $model = UserModel::findOne(['id' => $id]);
            $model->subscription_plan = 'trial';
            $model->subscription_expiry = date("Y-m-d H:i:s", strtotime("+" . self::SubscriptionTrialValue($type)->value . " days"));
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
                    $schoolID = SchoolTeachers::findOne(['teacher_id' => Yii::$app->user->id, 'status' => 1])->school_id;
                elseif ($type == 'school')
                    $schoolID = Utility::getSchoolAccess();
                $model = Schools::findOne(['id' => $schoolID]);
                $status = !empty($model->subscription_expiry) && strtotime($model->subscription_expiry) > time() ? true : false;
                $plan = $model->subscription_plan;
                $used_student = StudentSchool::find()->where(['school_id' => $model->id, 'status' => 1])->count();
                $limit = Options::findOne(['name' => $plan . '_school_students_limit']);
                $limit = $limit ? $limit->value : null;
                $unused_student = $limit - $used_student;
                $return = [
                    'status' => $status,
                    'expiry' => $model->subscription_expiry,
                    'plan' => $plan,
                    'limit' => $limit,
                    'used_student' => $used_student,
                    'unused_student' => $unused_student < 1 ? 0 : $unused_student,
                    'days_left' => self::subscriptionDaysLeft($model->subscription_expiry)
                ];

                return $statusOnly ? $status : $return;

            } else if ($type == 'student' || $type == 'parent') {
                if (isset($_GET['child']) && !empty($_GET['child'])) {
                    $childID = $_GET['child'];
                }
                if ($type == 'parent' && Parents::find()->where(['status' => 1, 'student_id' => $childID])->exists()) {
                    $studentID = $childID;
                } else if ($type == 'student') {
                    $studentID = Yii::$app->user->id;
                }

                if ($user = UserModel::findOne(['id' => $studentID, 'type' => 'student'])) {
                    $userStatus = !empty($user->subscription_expiry) && strtotime($user->subscription_expiry) > time() ? true : false;
                    $schoolSubStatus = false;
                    $status = false;
                    $is_school = 0;
                    if ($model = StudentSchool::find()->where(['status' => 1, 'student_id' => $studentID])->one()) {
                        $model = Schools::findOne(['id' => $model->school_id]);
                        $schoolSubStatus = !empty($model->subscription_expiry) && strtotime($model->subscription_expiry) > time() ? true : false;
                        $is_school = $schoolSubStatus ? 1 : 0;
                    }
                    if ($schoolSubStatus || $userStatus) {
                        $status = true;
                    }
                    $return = [
                        'status' => $status,
                        'expiry' => $user->subscription_expiry,
                        'plan' => $user->subscription_plan,
                        'is_school_sub' => $is_school,
                        'days_left' => self::subscriptionDaysLeft(isset($model->subscription_expiry) && strtotime($model->subscription_expiry) > strtotime($user->subscription_expiry) ? $model->subscription_expiry : $user->subscription_expiry)
                    ];
                    return $statusOnly ? $status : $return;
                }
            }
        } catch (\Exception $e) {
            $return = ['status' => false, 'expiry' => null, 'plan' => null, 'is_school_sub' => 0, 'days_left' => 0];
            return $statusOnly ? false : $return;
        }
    }

    public static function subscriptionDaysLeft($endDate)
    {
        $now = time();
        $endDate = strtotime($endDate);
        $datediff = $endDate < $now ? 0 : $endDate - $now;

        return round($datediff / (60 * 60 * 24));
    }

    public static function SchoolLimitStatus($id)
    {
        $school = Schools::findOne(['id' => $id]);
        $enrolled_students = self::StudentsInSchoolCount($id);
        $students_limit = self::StudentLimit($school->subscription_plan);
        $students_availability = $students_limit - $enrolled_students;
        return [
            'status' => $students_availability < 1 ? false : true,
            'used' => $enrolled_students,
            'remaining' => $students_availability
        ];
    }


    private static function StudentsInSchoolCount($id)
    {
        return StudentSchool::find()->where(['school_id' => $id, 'status' => 1])->count();
    }

    private static function StudentLimit($plan)
    {
        $plan_name = empty($plan) ? 'trial_school_students_limit' : $plan . '_school_students_limit';
        $model = Options::findOne(['name' => $plan_name]);
        return $model ? $model->value : 0;
    }

    public static function ActivateStudentTrial($student_id)
    {
        $option_model = self::SubscriptionTrialValue('student');
        $model = UserModel::findOne(['id' => $student_id, 'type' => 'student', 'subscription_plan' => null]);
        if (!$option_model || !$model) {
            return false;
        }
        $model->subscription_plan = 'trial';
        $model->subscription_expiry = date("Y-m-d H:i:s", strtotime("+" . $option_model->value . " days"));
        if (!$model->save()) {
            return false;
        }

        return true;
    }
}