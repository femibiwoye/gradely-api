<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Schools;
use app\modules\v2\school\models\PreferencesForm;
use app\modules\v2\school\models\SchoolProfile;
use app\modules\v2\teacher\models\TeacherUpdateEmailForm;
use app\modules\v2\teacher\models\TeacherUpdatePasswordForm;
use app\modules\v2\teacher\models\UpdateTeacherForm;
use Yii;
use app\modules\v2\models\{User, ApiResponse, UserPreference, StudentSchool, Classes};
use app\modules\v2\components\{SharedConstant};
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


/**
 * Auth controller
 */
class ProfileController extends ActiveController
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
            'class' => CustomHttpBearerAuth::className(),
        ];

        //Control user type that can access this
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        return Yii::$app->user->identity->type == 'school';
                    },
                ],
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

    /**
     * Login action.
     *
     * @return Response|string
     */

    public function actionUpdateEmail()
    {
        $model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();

        $form = new TeacherUpdateEmailForm();
        $form->attributes = Yii::$app->request->post();
        $form->user = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model->email = $form->email;
        if (!$form->sendEmail() || !$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Email is not updated!');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionUpdatePassword()
    {
        $model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();

        $form = new TeacherUpdatePasswordForm();
        $form->attributes = Yii::$app->request->post();
        $form->user = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$form->updatePassword()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Password is not updated!');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Password is successfully updated!');
    }

    public function actionUpdate()
    {
        $model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();

        $form = new UpdateTeacherForm();
        $form->attributes = Yii::$app->request->post();
        $form->user = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model = $form->updateTeacher()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Profile is not updated');
        }


        $model = array_merge(ArrayHelper::toArray($model), Utility::getSchoolAdditionalData($model->id));

        return (new ApiResponse)->success($model);
    }

    public function actionPreference()
    {
        $user_id = Yii::$app->user->id;
        $model = UserPreference::find()->andWhere(['user_id' => $user_id])->one();
        if ($model) {
            return (new ApiResponse)->success($model);
        } else {
            $model = new UserPreference;
            $model->user_id = $user_id;
            if (!$model->save()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User preference not added successfully');
            }
        }

        return (new ApiResponse)->success($model);
    }

    public function actionUpdatePreference()
    {
        $model = UserPreference::findOne(['user_id' => Yii::$app->user->id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found!');
        }

        $model->attributes = Yii::$app->request->post();
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User preference not updated');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionSchool()
    {
        $model = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        return (new ApiResponse)->success($model);

    }

    public function actionUpdateSchool()
    {
        $model = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $form = new SchoolProfile(['scenario' => SchoolProfile::SCENERIO_EDIT_SCHOOL_PROFILE]);
        $form->attributes = Yii::$app->request->post();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found!');
        }

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }
        //$model->attributes = $form->attributes;
        if (!$form->updateSchool($model)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'School profile not updated');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionDeletePersonalAccount()
    {
        $user_id = Yii::$app->user->id;
        $model = User::find()->andWhere(['id' => $user_id])->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User record not found');
        }

        $school = Schools::find()->where(['user_id' => $user_id]);
        if ($school->exists()) {
            $school = $school->one();
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, "You need to assign '$school->name' ownership to another user or delete school before you can delete your personal account.");
        }

        $model->email = $model->email . '-deleted';
        $model->phone = $model->phone . '-deleted';
        $model->status = SharedConstant::STATUS_DELETED;
        $model->token = null;
        $model->token_expires = null;
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User account not deleted!');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'User account deleted successfully');
    }

    public function actionDeleteSchoolAccount()
    {
        $user_id = Yii::$app->user->id;
        $model = User::find()->andWhere(['id' => $user_id])->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User record not found');
        }

        $form = new PreferencesForm(['scenario' => 'verify-password']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!Yii::$app->security->validatePassword($form->password, Yii::$app->user->identity->password_hash)) {
            $form->addError('password', 'Current password is incorrect!');
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $school = Schools::find()->where(['user_id' => $user_id]);
        if (!$school->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, "You do not have permission to delete this school");
        }

        if (!$school->one()->delete()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'School account not deleted!');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'School account deleted successfully');
    }

    public function actionStudentClasses($student_id) {
        $school_id = Utility::getSchoolAccess()[0];
        $model = new \yii\base\DynamicModel(compact('student_id', 'school_id'));
        $model->addRule(['student_id'], 'exist', ['targetClass' => StudentSchool::className(), 'targetAttribute' => ['student_id' => 'student_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $classes = Classes::find()
                    ->innerJoin('student_school', 'student_school.class_id = classes.id')
                    ->where(['student_school.student_id' => $student_id]);

        
        if (!$classes) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes not found!');
        }

        return (new ApiResponse)->success($classes->all(), ApiResponse::SUCCESSFUL, 'Classes found');
    }
}

