<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\InputNotification;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Parents;
use app\modules\v2\models\SchoolSubject;
use app\modules\v2\models\SignupForm;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\StudentSummerSchool;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use Yii;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\components\SharedConstant;
use yii\base\DynamicModel;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};

class SummerSchoolController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\UserModel';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];
        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                CustomHttpBearerAuth::className()
            ],
        ];
        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
    }


    public function actionConnectMyChild()
    {
        $user = Yii::$app->user->identity;
        if ($user->type != 'parent') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user request');
        }

        $school_id = Yii::$app->params['summerSchoolID'];
        $student_id = Yii::$app->request->post('student_id');
        $parent_id = $user->id;
        $course_id = Yii::$app->request->post('course_id');

        $form = new \yii\base\DynamicModel(compact('school_id', 'student_id', 'parent_id', 'course_id'));
        $form->addRule(['school_id', 'student_id', 'parent_id', 'course_id'], 'required');
        //$form->addRule(['course_id'], 'exist', ['targetClass' => Subjects::className(), 'targetAttribute' => ['course_id' => 'id']]);
        if ($user->type == 'parent') {
            $form->addRule(['parent_id', 'student_id'], 'exist', ['targetClass' => Parents::className(), 'targetAttribute' => ['parent_id', 'student_id']]);
        }
        //$form->addRule(['course_id', 'school_id'], 'exist', ['targetClass' => SchoolSubject::className(), 'targetAttribute' => ['course_id' => 'subject_id', 'school_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if (!is_array($course_id)) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Course must be an array');
        }

        if (count($course_id) > Subjects::find()->where(['id' => $course_id])->count()) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'One or more course is invalid');
        }

        if (!$model = StudentSummerSchool::findOne(['student_id' => $student_id])) {
            $model = new StudentSummerSchool();
            $model->subjects = $course_id;
        }else{
            $model->subjects = array_keys(array_flip(array_merge($model->subjects,$course_id)));
        }
        $class = Utility::StudentChildClass($student_id, 1);
        $classes = Classes::findOne(['school_id' => $school_id, 'global_class_id' => $class]);
        $model->student_id = $student_id;
        $model->parent_id = $parent_id;
        $model->school_id = $school_id;
        $model->global_class = $class;
        $model->class_id = $classes->id;
        $model->status = 1;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not saved');
        }
        return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL, 'Record saved');
    }

    public function actionConnectByCode()
    {
        $user = Yii::$app->user->identity;
        if ($user->type != 'parent') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user request');
        }

        $school_id = Yii::$app->params['summerSchoolID'];
        $code = Yii::$app->request->post('code');
        $parent_id = $user->id;
        $course_id = Yii::$app->request->post('course_id');

        $form = new \yii\base\DynamicModel(compact('school_id', 'code', 'parent_id', 'course_id'));
        $form->addRule(['school_id', 'code', 'parent_id', 'course_id'], 'required');
        //$form->addRule(['course_id'], 'exist', ['targetClass' => Subjects::className(), 'targetAttribute' => ['course_id' => 'id']]);
        $form->addRule(['code'], 'exist', ['targetClass' => User::className(), 'targetAttribute' => ['code']]);
        //$form->addRule(['course_id', 'school_id'], 'exist', ['targetClass' => SchoolSubject::className(), 'targetAttribute' => ['course_id' => 'subject_id', 'school_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $student = User::find()->select(['id', 'code', 'class'])->where(['code' => $code, 'type' => 'student'])->asArray()->one();

        if (!is_array($course_id)) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Course must be an array');
        }

        if (count($course_id) > Subjects::find()->where(['id' => $course_id])->count()) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'One or more course is invalid');
        }
        if (!$model = StudentSummerSchool::findOne(['student_id' => $student['id']])) {
            $model = new StudentSummerSchool();
            $model->subjects = $course_id;
        }else{
            $model->subjects = array_keys(array_flip(array_merge($model->subjects,$course_id)));
        }
        $class = Utility::getStudentClass(1, $student['id']);
        $classes = Classes::findOne(['school_id' => $school_id, 'global_class_id' => $class]);
        $model->student_id = $student['id'];
        $model->parent_id = $parent_id;
        $model->school_id = $school_id;
//        $model->subjects = $course_id;
        $model->global_class = $class;
        $model->class_id = $classes->id;
        $model->status = 1;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not saved');
        }

        if (!Parents::find()->where(['student_id' => $model->student_id, 'parent_id' => $model->parent_id, 'status' => 1])->exists()) {
            $newParent = new Parents();
            $newParent->parent_id = $model->parent_id;
            $newParent->student_id = $model->student_id;
            $newParent->status = 1;
            $newParent->save();
        }

        return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL, 'Record saved');
    }

    public function actionConnectNewChild()
    {
        $parentUser = Yii::$app->user->identity;
        if ($parentUser->type != 'parent') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user request');
        }
        $students = Yii::$app->request->post('students');

        if (empty($students)) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Students cannot be blank');
        }
        if (!is_array($students)) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Must be an array');
        }
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            $relationship = 'guardian';
            $saveCount = 0;
            foreach ($students as $student) {

                $model = new SignupForm(['scenario' => 'parent-student-signup']);
                $model->attributes = $student;

                $studentModel = UserModel::findOne(['firstname' => $model->first_name, 'lastname' => $model->last_name, 'type' => 'student']);
                if ($studentModel && Parents::find()->where(['student_id' => $studentModel->id, 'parent_id' => Yii::$app->user->id, 'status' => 1])->exists()) {
                    return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Child already exist');
                }


                if (!$model->validate())
                    return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);

                if ($user = $model->signup('student')) {

                    $parent = new Parents;
                    $parent->parent_id = Yii::$app->user->id;
                    $parent->student_id = $user->id;
                    $parent->role = $relationship;
                    $parent->inviter = 'parent';
                    $parent->status = SharedConstant::VALUE_ONE;
                    if (!$parent->save())
                        return (new ApiResponse)->error($parent->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'An error occurred');

                    //Notification that parent add child
                    $notification = new InputNotification();
                    $notification->NewNotification('parent_adds_student', [['student_id', $user->id], ['parent_id', Yii::$app->user->id], ['password', $model->password]]);
                }


                if (!is_array($student['course_id'])) {
                    return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Course must be an array');
                }

                if (count($student['course_id']) > Subjects::find()->where(['id' => $student['course_id']])->count()) {
                    return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'One or more course is invalid');
                }

                if (!$model = StudentSummerSchool::findOne(['student_id' => $user->id])) {
                    $model = new StudentSummerSchool();
                }
                $school_id = Yii::$app->params['summerSchoolID'];
                $class = Utility::StudentChildClass($user->id, 1);
                $classes = Classes::findOne(['school_id' => $school_id, 'global_class_id' => $class]);
                $model->student_id = $user->id;
                $model->parent_id = $parentUser->id;
                $model->school_id = $school_id;
                $model->subjects = $student['course_id'];
                $model->global_class = $class;
                $model->class_id = $classes->id;
                $model->status = 1;
                if (!$model->save()) {
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not saved');
                }
                $saveCount++;
            }
            $dbtransaction->commit();
            return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL, $saveCount . ' record added');
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            return (new ApiResponse)->success(false, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not saved');
        }
    }

    public function actionSwitchSummerSchool()
    {
        $user = Yii::$app->user->identity;

        if ($user->type != 'student' && $user->type != 'parent') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user request');
        }

        $school_id = Yii::$app->params['summerSchoolID'];
        $summer_school = Yii::$app->request->post('summer_school');
        if ($user->type == 'student') {
            $student_id = $user->id;
            $parent_id = null;
        } else {
            $student_id = Yii::$app->request->post('student_id');
            $parent_id = $user->id;
        }
        //$studentID = Utility::getParentChildID();


        $form = new \yii\base\DynamicModel(compact('summer_school', 'student_id', 'parent_id'));
        $form->addRule(['summer_school', 'student_id'], 'required');
        if ($user->type == 'parent') {
            $form->addRule(['parent_id', 'student_id'], 'exist', ['targetClass' => Parents::className(), 'targetAttribute' => ['parent_id', 'student_id']]);
        }
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if (!$summerSchool = StudentSummerSchool::findOne(['student_id' => $student_id])) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You need to configure summer school');
        }

        if (!$studentSchool = StudentSchool::findOne(['student_id' => $student_id, 'status' => 1, 'is_active_class' => 1, 'current_class' => 1])) {
            $studentSchool = new StudentSchool();
            $studentSchool->student_id = $student_id;
            $studentSchool->status = 1;
        }
        $dbtransaction = Yii::$app->db->beginTransaction();
        // try {
        //$summerReplicate = $summerSchool;
        $studentSchoolReplicate = clone $studentSchool;

        if (isset($studentSchool->in_summer_school) && $summer_school == $studentSchool->in_summer_school) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Your summer school is already ' . $summer_school);
        }

        //if ($summer_school) {
        $studentSchool->in_summer_school = $summer_school;
        $studentSchool->school_id = $summerSchool->school_id;
        $studentSchool->class_id = $summerSchool->class_id;

        $summerSchool->school_id = empty($studentSchoolReplicate->school_id) ? $school_id : $studentSchoolReplicate->school_id;
        $summerSchool->class_id = $studentSchoolReplicate->class_id;
        if (!$studentSchool->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save school record');
        }

        if (!$summerSchool->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save summer record');
        }
        $dbtransaction->commit();
        return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL, 'Updated');
        //}

        return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL, 'No changes made');
//        } catch (\Exception $e) {
//            $dbtransaction->rollBack();
//            return (new ApiResponse)->success(false, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not saved');
//        }
    }

    public function actionGetSummerCourses()
    {
        $user = Yii::$app->user->identity;
        if ($user->type != 'student' && $user->type != 'parent') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user request');
        }
        $studentID = Utility::getParentChildID();

        $courses = Subjects::find()
            ->select([
                'subjects.id', 'name', 'description', Yii::$app->params['subjectImage'],
//                new Expression("$studentID as student_id"),
                new Expression("JSON_CONTAINS(
            `sss`.`subjects`,
            CONCAT(`subjects`.`id`)
        ) as is_enrolled")
            ])
            ->leftJoin('student_summer_school sss', "sss.student_id =$studentID")
            ->where(['summer_school' => 1])
            ->asArray()
            ->all();

        foreach ($courses as $key=> $course){
            $courses[$key]['is_enrolled'] =(int)$course['is_enrolled'];
            $courses[$key]['id'] =(int)$course['id'];
        }


        $return = array_merge(['courses' => $courses],
            ['is_summer_student' => StudentSummerSchool::find()->where(['student_id' => $studentID])->exists()]);

        return (new ApiResponse)->success($return, ApiResponse::SUCCESSFUL);

    }

    public function actionChildEnrolling()
    {
        $user = Yii::$app->user->identity;
        if ($user->type != 'student') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user request');
        }

        $school_id = Yii::$app->params['summerSchoolID'];
        $student_id = $user->id;
        $course_id = Yii::$app->request->post('course_id');

        $form = new \yii\base\DynamicModel(compact('school_id', 'student_id', 'course_id'));
        $form->addRule(['school_id', 'student_id', 'course_id'], 'required');

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if (!is_array($course_id)) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Course must be an array');
        }

        if (count($course_id) > Subjects::find()->where(['id' => $course_id])->count()) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'One or more course is invalid');
        }

        if (!$model = StudentSummerSchool::findOne(['student_id' => $student_id])) {
            $model = new StudentSummerSchool();
            $model->subjects = $course_id;
        }else{
            $model->subjects = array_keys(array_flip(array_merge($model->subjects,$course_id)));
        }
        $class = Utility::StudentChildClass($student_id, 1);
        $classes = Classes::findOne(['school_id' => $school_id, 'global_class_id' => $class]);
        $model->student_id = $student_id;
        $model->school_id = $school_id;
        //$model->subjects = $course_id;
        $model->global_class = $class;
        $model->class_id = $classes->id;
        $model->status = 1;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not saved');
        }
        return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL, 'Record saved');
    }

}
