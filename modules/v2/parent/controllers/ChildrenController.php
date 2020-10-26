<?php

namespace app\modules\v2\parent\controllers;

use app\modules\v2\components\{InputNotification, Pricing};
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\Classes;
use app\modules\v2\models\GlobalClass;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\components\Utility;
use app\modules\v2\controllers\AuthController;
use app\modules\v2\models\SignupForm;
use app\modules\v2\models\SubscriptionChildren;
use app\modules\v2\models\SubscriptionPaymentDetails;
use app\modules\v2\models\UserModel;
use yii\base\DynamicModel;
use app\modules\v2\models\VideoContent;
use Yii;
use app\modules\v2\components\CustomHttpBearerAuth;
//models
use app\modules\v2\models\{Parents, ApiResponse, User};


use yii\filters\AccessControl;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;

/**
 * module parent/Children controller
 */
class ChildrenController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Parents';

    /**
     * @return array
     */
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
            'class' => CustomHttpBearerAuth::className(),
        ];

        //Control user type that can access this
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        return Yii::$app->user->identity->type == 'parent';
                    },
                ],
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['view']);
        return $actions;
    }

    public function actionList()
    {
        $parent_id = Yii::$app->user->id;
        $students = Parents::find()
            ->joinWith(['studentClass'])
            ->where(['parent_id' => $parent_id])
            ->all();

        foreach ($students as $k => $student) {
            $students[$k] = User::find()
                ->where(['id' => $student->student_id])
                ->one();

            $students[$k] = array_merge(ArrayHelper::toArray($students[$k]), ['class' => $student->studentClass]);
        }

        if (!$students) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No parent available!');
        }

        return (new ApiResponse)->success($students, ApiResponse::SUCCESSFUL);
    }


    public function actionUpdateChildClass()
    {


        $class_id = Yii::$app->request->post('class_id');
        $password = Yii::$app->request->post('password');
        //$curriculum = Yii::$app->request->post('curriculum');
        $child_id = Yii::$app->request->post('child_id');

        $form = new DynamicModel(compact(['class_id', 'password', 'curriculum', 'child_id']));
        $form->addRule(['class_id', 'password', 'child_id'], 'required');
        $form->addRule(['class_id'], 'exist', ['targetClass' => GlobalClass::className(), 'targetAttribute' => ['class_id' => 'id']]);
        $form->addRule(['child_id'], 'exist', ['targetClass' => User::className(), 'targetAttribute' => ['child_id' => 'id']]);


        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);

        $user = User::findOne($child_id);

        $parent = Parents::findOne(['student_id' => $child_id, 'status' => SharedConstant::VALUE_ONE]);
        if (!Yii::$app->security->validatePassword($password, Yii::$app->user->identity->password_hash)) {
            $form->addError('password', 'Current password is incorrect!');
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$parent)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child not found');

        $class = GlobalClass::findOne(['id' => $class_id, 'status' => SharedConstant::VALUE_ONE]);

        if (!$class)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found');

        $student_school = StudentSchool::findOne(['student_id' => $child_id]);

        if ($student_school)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'School manages child class');


        if (($class_id - $user->class) > 1)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child new class does not correlate with previous class.');


        $user->class = $class_id;

        if (!$user->save())
            return (new ApiResponse)->error($user->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child class not updated');


        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Child class updated');
    }

    public function actionResetChildPassword()
    {

        $student_code = Yii::$app->request->post('student_code');
        $password = Yii::$app->request->post('password');
        $type = 'student';

        $form = new DynamicModel(compact(['password', 'student_code', 'type']));
        $form->addRule(['password', 'student_code'], 'required');
        $form->addRule(['student_code'], 'exist', ['targetClass' => User::className(), 'targetAttribute' => ['student_code' => 'code', 'type']]);

        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);


        $user = User::findOne(['code' => $student_code, 'type' => $type]);

        $parent = Parents::findOne(['student_id' => $user->id, 'status' => SharedConstant::VALUE_ONE]);


        if (!$parent)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not have relationship with this child.');

        $user->password_hash = Yii::$app->security->generatePasswordHash($password);

        if (!$user->save())
            return (new ApiResponse)->error($user->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child password not updated');


        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Password successfully updated');

    }

    public function actionUnlinkChild($child_id)
    {

        $parent = Parents::findOne(['parent_id' => Yii::$app->user->id, 'student_id' => $child_id, 'status' => 1]);

        if (!$parent)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child not found');

        if (!$parent->delete())
            return (new ApiResponse)->error($parent->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child cannot be unlinked');

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Child successfully unlinked');

    }

    public function actionSearchStudentCode()
    {

        $code = Yii::$app->request->post('code');

        $form = new DynamicModel(compact(['code']));
        $form->addRule(['code'], 'required');

        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);

        $user = User::findOne(['code' => $code]);

        if (!$user)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student not found');

        if (!$user->code)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student Code not found');

        if ($studentSchool = StudentSchool::findOne(['student_id' => $user->id, 'status' => 1])) {
            $class = Classes::findOne(['id' => $studentSchool->class_id]);
            $school = ['class_name' => $class->class_name, 'school' => $class->school->name];
        } else {
            $school = ['class_name' => null, 'school' => null];
        }


        return (new ApiResponse)->success(array_merge(ArrayHelper::toArray($user), $school), ApiResponse::SUCCESSFUL, 'Student found');

    }

    public function actionConnectStudentCode()
    {

        $code = Yii::$app->request->post('code');
        $relationship = Yii::$app->request->post('relationship');

        $form = new DynamicModel(compact(['code', 'relationship']));
        $form->addRule(['code', 'relationship'], 'required');


        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');


        $user = User::findOne(['code' => $code]);

        $parent = Parents::find()->where([
            'student_id' => $user->id,
            'status' => SharedConstant::VALUE_ONE,
            'parent_id' => Yii::$app->user->id,
        ])
            ->andWhere(['is not', 'code', null])
            ->one();

        if (!$user)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student not found');

        if ($parent)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child already exists');

        if (!$user->code)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student Code not found');


        $parent = new Parents();
        $parent->parent_id = Yii::$app->user->id;
        $parent->code = $code;
        $parent->student_id = $user->id;
        $parent->role = $relationship;
        $parent->status = SharedConstant::VALUE_ONE;
        $parent->inviter = 'parent';

        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$parent->save())
                return (new ApiResponse)->error($parent->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Error found');

            /*$notification = new InputNotification();
            if (!$notification->NewNotification('parent_connects_student', [['parent_id', Yii::$app->user->id]]))
                return false;*/

            Pricing::ActivateStudentTrial($user->id);

            $dbtransaction->commit();
        } catch (Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }

        return (new ApiResponse)->success($user, ApiResponse::SUCCESSFUL, 'Parent Child saved');

    }

    public function actionSignupChild()
    {

        $relationship = Yii::$app->request->post('relationship');

        $form = new DynamicModel(compact(['relationship']));
        $form->addRule(['relationship'], 'required');


        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');

        $model = new SignupForm(['scenario' => 'parent-student-signup']);
        $model->attributes = Yii::$app->request->post();

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
        }
        Pricing::ActivateStudentTrial($user->id);
        return (new ApiResponse)->success(array_merge(ArrayHelper::toArray($user), ['password' => $model->password]), ApiResponse::SUCCESSFUL, 'Child successfully added');

    }
}
