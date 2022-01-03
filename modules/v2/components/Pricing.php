<?php

namespace app\modules\v2\components;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\StudentSummerSchool;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use yii\base\Widget;
use Yii;
use app\modules\v2\models\{Schools, Options, SchoolTeachers, StudentSchool, Parents, TeacherClass};
use yii\helpers\ArrayHelper;

class Pricing extends Widget
{
    public static function ActivateTrial($id, $type)
    {
        if ($type == 'school') {
            $model = Schools::findOne(['id' => $id]);
            $model->subscription_plan = 'trial';
            $model->subscription_expiry = date("Y-m-d H:i:s", strtotime("+" . self::SubscriptionTrialValue($type)->value . " days"));
        } else if ($type == 'student') {
            //This is for student trial
            /*            $model = UserModel::findOne(['id' => $id]);
                        $model->subscription_plan = 'trial';
                        $model->subscription_expiry = date("Y-m-d H:i:s", strtotime("+" . self::SubscriptionTrialValue($type)->value . " days"));*/
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

                //Override for freemium
                $return = [
                    'status' => true,
                    'expiry' => null,
                    'plan' => null,
                    'limit' => 0,
                    'used_student' => 0,
                    'unused_student' => 0,
                    'days_left' => 0
                ];

                return $statusOnly ? true : $return;


                if ($type == 'teacher') {
                    if (!empty(Yii::$app->request->get('class_id')))
                        $classes = Yii::$app->request->get('class_id');
                    else
                        $classes = Yii::$app->request->post('class_id');
                    $schoolID = TeacherClass::findOne(['teacher_id' => Yii::$app->user->id, 'status' => 1, 'class_id' => $classes])->school_id;
                } elseif ($type == 'school')
                    $schoolID = Utility::getSchoolAccess();
                $model = Schools::findOne(['id' => $schoolID]);
                $status = !empty($model->subscription_expiry) && strtotime($model->subscription_expiry) > time() ? true : false;
                $plan = $model->subscription_plan;
                $used_student = StudentSchool::find()->where(['school_id' => $model->id, 'status' => 1, 'is_active_class' => 1])->count();
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

                $user = UserModel::findOne(['id' => $studentID, 'type' => 'student']);
                //Override for freemium
                $return = array_merge([
                    'status' => true,
                    'expiry' => null,
                    'plan' => null,
                    'is_school_sub' => 1,
                    'school_active' => 1,
                    'days_left' => 30
                ], ['lms' => true, 'status' => !empty($user->subscription_expiry) && strtotime($user->subscription_expiry) > time() ? true : false]);
                return $statusOnly ? !empty($user->subscription_expiry) && strtotime($user->subscription_expiry) > time() ? true : false : $return;


                if ($user = UserModel::findOne(['id' => $studentID, 'type' => 'student'])) {
                    $userStatus = !empty($user->subscription_expiry) && strtotime($user->subscription_expiry) > time() ? true : false;
                    $schoolSubStatus = false;
                    $status = false;
                    $is_school = 0;
                    $schoolActive = 0;
                    $plan = $user->subscription_plan;
                    if ($studentModel = StudentSchool::find()->where(['status' => 1, 'student_id' => $studentID, 'is_active_class' => 1])->one()) {
                        $model = Schools::findOne(['id' => $studentModel->school_id]);
                        $schoolSubStatus = !empty($model->subscription_expiry) && strtotime($model->subscription_expiry) > time() ? true : false;
                        $is_school = $schoolSubStatus ? 1 : 0;
                        $plan = $model->subscription_plan;
                        $schoolActive = 1;
                    }
                    if ($schoolSubStatus || $userStatus) {
                        $status = true;
                    }


                    if (isset($studentModel) && $studentModel->in_summer_school == 1) {
                        if (StudentSummerSchool::find()->where(['student_id' => $studentID, 'status' => 1, 'summer_payment_status' => 'paid'])->exists()) {
                            $lmsCatchupStatus = ['lms' => true, 'status' => true];
                        } else {
                            $lmsCatchupStatus = ['lms' => false, 'status' => false];
                        }
                    } else {
                        $lmsCatchupStatus = self::StudentLmsCatchupStatus($studentID, $status, $userStatus, $schoolSubStatus, $is_school, $plan);
                    }
                    //I have only merged catchup and lms status to full return.
                    $return = array_merge([
                        'status' => $status,
                        'expiry' => $user->subscription_expiry,
                        'plan' => $plan,
                        'is_school_sub' => $is_school,
                        'school_active' => $schoolActive,
                        'days_left' => self::subscriptionDaysLeft(isset($model->subscription_expiry) && strtotime($model->subscription_expiry) > strtotime($user->subscription_expiry) ? $model->subscription_expiry : $user->subscription_expiry)
                    ], $lmsCatchupStatus);
                    return $statusOnly ? $status : $return;
                    //return $statusOnly ? array_merge(['status'=>$status],$lmsCatchupStatus) : $return; // Final result
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
        return StudentSchool::find()->where(['school_id' => $id, 'status' => 1, 'is_active_class' => 1])->count();
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

    /**
     * Get Catchup and LMS subscription status of a student
     *
     * @param $studentID
     * @param $subStatus
     * @param $studentSubStatus
     * @param $schoolSubStatus
     * @param bool $isSchool
     * @return array
     */
    public static function StudentLmsCatchupStatus($studentID, $subStatus, $studentSubStatus, $schoolSubStatus, $isSchool = false, $plan = null)
    {
        $lms = false;
        $catchup = false;
        if ($subStatus) {
            if ($isSchool && $schoolSubStatus) {
                $studentSchool = StudentSchool::findOne(['student_id' => $studentID, 'status' => 1, 'is_active_class' => 1]);
                if ($studentSchool->subscription_status == 'basic') {
                    $lms = true;
                }
                if ($studentSchool->subscription_status == 'premium' || $plan == 'trial') {
                    $catchup = true;
                    $lms = true;
                }
            }
            if ($studentSubStatus) {
                $catchup = true;
            }
        }

        /// if lms or catchup does not have subscription, check if student is in summer school
        /// being in summer school and payment status is paid automatically subscribed catchup and lms.
        /// LMS in this case is the summer school feed content, assessments, etc
        if (!$catchup || !$lms) {
            if (StudentSummerSchool::find()->where(['student_id' => $studentID, 'status' => 1, 'summer_payment_status' => 'paid'])->exists()) {
                $catchup = true;
                $lms = true;
            }
        }


//        return ['lms' => $lms, 'catchup' => $catchup, 'subStatus' => $subStatus, 'isSchool' => $isSchool, 'schoolSubStatus' => $schoolSubStatus];
        return ['lms' => $lms, 'status' => $catchup];
    }

    /**
     * This is called when teacher or school added a new student. If there is available slot, it add the student to the slot
     * @param $students
     * @return bool
     */
    public static function SchoolAddStudentSubscribe($students)
    {

        $students = StudentSchool::find()->where(['student_id' => $students, 'status' => 1, 'is_active_class' => 1, 'subscription_status' => null])->all();
        foreach ($students as $student) {
            $school = Schools::findOne(['id' => $student->school_id]);
            $status = Utility::SchoolStudentSubscriptionDetails($school);
            if ($status['premium']['remaining'] > 0) {
                $student->subscription_status = 'premium';
                $student->save();
            } elseif ($status['basic']['remaining'] > 0) {
                $student->subscription_status = 'basic';
                $student->save();
            }
            return true;
        }
        return false;
    }

    /**
     * This subscribe students in a school if the school is subscribing for the first time and no student has subscription before now.
     * @param Schools $school
     */
    public static function SchoolStudentFirstTimeSubscription(Schools $school)
    {
        if (!StudentSchool::find()->where(['school_id' => $school->id, 'status' => 1, 'is_active_class' => 1, 'subscription_status' => ['basic', 'premium']])->exists() && $school->basic_subscription > 0) {
            $students = StudentSchool::find()->where(['school_id' => $school->id, 'status' => 1, 'is_active_class' => 1])->all();
            for ($i = 1; $i <= $school->basic_subscription; $i++) {
                if (isset($students[$i])) {
                    Pricing::SubscribeChildFunction($students[$i], $school);
                } else {
                    break;
                }
            }
        }
    }


    /**
     * This is inner function for school students subscription
     * @param StudentSchool $student
     * @param Schools $school
     * @return bool
     */
    public static function SubscribeChildFunction(StudentSchool $student, Schools $school)
    {
        if ($school->premium_subscription > StudentSchool::find()->where(['school_id' => $school->id, 'status' => 1, 'is_active_class' => 1, 'subscription_status' => 'premium'])->exists()) {
            $student->subscription_status = 'premium';
        } else {
            $student->subscription_status = 'basic';
        }
        $student->save();
    }

}