<?php

namespace app\modules\v2\sms\controllers;

use app\modules\v2\components\InputNotification;
use app\modules\v2\components\Pricing;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\SmsAuthentication;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Parents;
use app\modules\v2\models\SignupForm;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\User;
use Yii;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


class AuthController extends ActiveController
{
    public $modelClass = 'app\modules\v2\sms\models\Schools';

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

    public function beforeAction($action)
    {
        if (!SmsAuthentication::checkStatus()) {
            $this->asJson(\Yii::$app->params['customError401']);
            return false;
        }
        return parent::beforeAction($action);
    }


    public function actionSignup($type)
    {
        if (!in_array($type, SharedConstant::ACCOUNT_TYPE)) {
            return (new ApiResponse)->error(null, ApiResponse::NOT_FOUND, 'This is an unknown user type');
        }

        $form = new SignupForm(['scenario' => "$type-signup"]);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$user = $form->signup($type)) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'User is not created successfully');
        }

        $user->updateAccessToken();
        if ($user->type == 'student') {
            $this->NewStudent(Yii::$app->request->post('class_code'), $user->id);

            $parentID = Yii::$app->request->post('parent_id');
            if(!empty($parentID)){
                if(User::find()->where(['id'=>$parentID,'type'=>'parent'])->exists()) {
                    $parent = new Parents();
                    $parent->parent_id = $parentID;
                    $parent->student_id = $user->id;
                    $parent->status = 1;
                    $parent->inviter = 'sms';
                    $parent->role = Yii::$app->request->post('relationship');
                    $parent->save();
                }
            }

        } elseif ($user->type == 'teacher') {
            $class_code = Yii::$app->request->post('class_code');
            $this->AddTeacher($class_code, $user->id);
        } elseif ($user->type == 'parent') {
            $studentCode = Yii::$app->request->post('student_code');
            $relationship = Yii::$app->request->post('relationship');
            $this->ConnectStudentCode($studentCode, $relationship, $user->id);
        }

        return (new ApiResponse)->success($user, null, 'You have successfully signed up as a ' . $type);
    }

    /**
     * Connect student to class
     * @param $classCode
     * @param $studentID
     * @return ApiResponse
     */
    public function NewStudent($classCode, $studentID)
    {
        if (!$classCode) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class code is required');
        }

        $class = Classes::findOne(['class_code' => $classCode]);
        if (!$class) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model = new StudentSchool;
        $model->student_id = $studentID;
        $model->school_id = $class->school_id;
        $model->class_id = $class->id;
        $model->invite_code = $classCode;
        $model->status = 1;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not validated');
        }
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student not joined saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Student joined the class');
    }

    public function AddTeacher($class_code, $teacher_id)
    {

        if (!$class_code) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Provide class code.');
        }

        $class = Classes::findOne(['class_code' => $class_code]);
        if (!$class) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found!');
        }

        if (!$class->school) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'School not found!');
        }


        if (TeacherClass::find()->where(['teacher_id' => $teacher_id, 'school_id' => $class->school->id, 'class_id' => $class->id, 'status' => 1])->exists())
            return (new ApiResponse)->success(null, ApiResponse::ALREADY_TAKEN, 'Teacher already added to class!');

        $model = new TeacherClass;
        $model->teacher_id = $teacher_id;
        $model->school_id = $class->school->id;
        $model->class_id = $class->id;
        $model->status = 1;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher is not successfully added!');
        }
        $model->addSchoolTeacher(1);

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Teacher added successfully');
    }


    public function ConnectStudentCode($studentCode, $relationship, $parentID)
    {

        $code = $studentCode;
        $form = new DynamicModel(compact(['code', 'relationship']));
        $form->addRule(['code', 'relationship'], 'required');


        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');


        $user = User::findOne(['code' => $code]);

        $parent = Parents::find()->where([
            'student_id' => $user->id,
            'status' => SharedConstant::VALUE_ONE,
            'parent_id' => $parentID,
        ])
            //->andWhere(['is not', 'code', null])
            ->one();

        if (!$user)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student not found');

        if ($parent)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child already exists');

        if (!$user->code)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student Code not found');


        $parent = new Parents();
        $parent->parent_id = $parentID;
        $parent->code = $code;
        $parent->student_id = $user->id;
        $parent->role = $relationship;
        $parent->status = SharedConstant::VALUE_ONE;
        $parent->inviter = 'parent';

        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$parent->save())
                return (new ApiResponse)->error($parent->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Error found');

            if ($parent->scenario == 'parent-student-signup') {
                //Notification that parent add child
                $notification = new InputNotification();
                $notification->NewNotification('parent_connects_student', [['student_id', $user->id]]);
            }

            // Notification to welcome user
            $notification = new InputNotification();
            $notification->NewNotification('welcome_' . $user->type, [[$user->type . '_id', $user->id]]);

            // Pricing::ActivateStudentTrial($user->id); // Student trial

            $dbtransaction->commit();
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }

        return (new ApiResponse)->success($user, ApiResponse::SUCCESSFUL, 'Parent Child saved');

    }


}