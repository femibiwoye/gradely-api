<?php

namespace app\modules\v2\controllers;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\handler\ClickActionLogger;
use app\modules\v2\models\handler\ClickActionLoggerDetails;
use app\modules\v2\models\Questions;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\StudentSummerSchool;
use app\modules\v2\models\TeacherClass;
use Yii;
use yii\db\Exception;
use yii\db\Expression;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;


/**
 * Auth controller
 */
class TestController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];


        return $behaviors;
    }


    public function actionJson()
    {
        return Questions::find()
            ->select([
                new Expression("JSON_CONTAINS(
           LOWER(`answer`),
            '\"one\"'
        ) as is_enrolled")
            ])
            ->where(['id' => 14933])
            ->asArray()
            ->one();
    }

    public function actionPromoteStudent()
    {
        $schoolID = 188;
        $promoterID = 4897;
        $students = StudentSchool::find()->where(['school_id' => $schoolID, 'session' => '2020-2021', 'status' => 1])->all();
        foreach ($students as $student) {
            $currentStudents = StudentSchool::find()->select(['id', 'student_id'])->where(['class_id' => $student->class_id, 'school_id' => $student->school_id, 'status' => 1, 'is_active_class' => 1, 'student_id' => $student->student_id])->one();
            if (empty($currentStudents) && StudentSchool::find()->where(['class_id' => $student->class_id, 'school_id' => $student->school_id, 'status' => 0, 'is_active_class' => 0, 'student_id' => $student->student_id])->exists()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'This child is already promoted from this class');
            }

            $dbtransaction = Yii::$app->db->beginTransaction();
            try {
                if (!isset($currentStudents->student_id)) {
                    continue;
                }
                if (!StudentSchool::updateAll(['status' => 0, 'is_active_class' => 0], ['student_id' => $currentStudents->student_id, 'status' => 1, 'school_id' => $student->school_id, 'id' => $currentStudents->id]))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Previous class was not updated');

                $globalClassID = $student->class->global_class_id;
                if ($student->class->global_class_id != 12) {

                    if ($globalClassID == 15) {
                        $newClassGlobal = 1;
                    } else {
                        $newClassGlobal = $globalClassID + 1;
                    }
                    $newClassObject = Classes::findOne(['global_class_id' => $newClassGlobal, 'school_id' => $schoolID]);
//                foreach ($currentStudents as $student) {
                    $newClass = new StudentSchool();
                    $newClass->student_id = $currentStudents->student_id;
                    $newClass->status = 1;
                    $newClass->school_id = $student->school_id;
                    $newClass->promoted_from = $student->class_id;
                    $newClass->class_id = $newClassObject->id;
                    $newClass->promoted_by = $promoterID;
                    $newClass->promoted_at = date('Y-m-d H:i:s');
                    $newClass->session = '2021-2022';
//                    return $newClass->promoted_from.' - '.$newClassObject->id;
                    if (!$newClass->save()) {
                        return (new ApiResponse)->error($newClass->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
                    }
//                }
                }


                $dbtransaction->commit();
            } catch (Exception $e) {
                $dbtransaction->rollBack();
                return $e;
            }
        }
        return true;
    }

    /**
     * Return students from summer school back to their real school
     *
     * @return ApiResponse|int|string
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function actionSummer()
    {

        $schoolID = null;
        $dbtransaction = Yii::$app->db->beginTransaction();
        foreach (StudentSchool::find()->where(['school_id' => $schoolID])->all() as $key => $studentSchool) {
            $studentSchoolReplicate = clone $studentSchool;
            if ($summerSchool = StudentSummerSchool::find()->where(['<>', 'school_id', $schoolID])->andWhere(['student_id' => $studentSchool->student_id])->one()) {
                $studentSchool->in_summer_school = 0;
                $studentSchool->school_id = $summerSchool->school_id;
                $studentSchool->class_id = $summerSchool->class_id;

                $summerSchool->school_id = empty($studentSchoolReplicate->school_id) ? $schoolID : $studentSchoolReplicate->school_id;
                $summerSchool->class_id = $studentSchoolReplicate->class_id;
                if (!$studentSchool->save()) {
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save school record');
                } else {
                    if (!empty($studentSchool->class_id) && $studentSchool->class_id = $summerSchool->class_id && $studentSchool->in_summer_school == 0) {
                        $studentSchool->delete();
                    }
                }

                if (!$summerSchool->save()) {
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save summer record');
                }
            }
        };
        $dbtransaction->commit();
        return $key;
        //return StudentSummerSchool::find()->where(['summer_payment_status'=>'paid'])->count();


    }
}

