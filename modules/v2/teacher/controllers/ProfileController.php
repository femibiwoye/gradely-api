<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use Yii;
use app\modules\v2\models\{User, ApiResponse, UserPreference};
use app\modules\v2\teacher\models\{TeacherUpdateEmailForm,
    TeacherUpdatePasswordForm,
    TeacherUpdatePhoneForm,
    UpdateTeacherForm
};
use app\modules\v2\components\{SharedConstant};
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};


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
            'class' => CompositeAuth::className(),
            'authMethods' => [
                CustomHttpBearerAuth::className(),
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
        if ($model->type != SharedConstant::TYPE_TEACHER || !$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher not found!');
        }

        $form = new TeacherUpdateEmailForm;
        $form->attributes = Yii::$app->request->post();
        $form->user = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $model->attributes = $form->attributes;
        if (!$form->sendEmail() || !$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Email is not updated!');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionUpdatePhone()
    {
        $model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();
        if ($model->type != SharedConstant::TYPE_TEACHER || !$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher not found!');
        }

        $form = new TeacherUpdatePhoneForm();
        $form->attributes = Yii::$app->request->post();
        $form->user = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $model->attributes = $form->attributes;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Phone number is not updated!');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionUpdatePassword()
    {
        $model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();
        if ($model->type != SharedConstant::TYPE_TEACHER || !$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher not found!');
        }

        $form = new TeacherUpdatePasswordForm;
        $form->attributes = Yii::$app->request->post();
        $form->user = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$form->updatePassword()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Password is not updated!');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Password is successfully updated!');
    }

    public function actionUpdate()
    {
        $model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();
        if ($model->type != SharedConstant::TYPE_TEACHER || !$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher not found!');
        }

        $form = new UpdateTeacherForm;
        $form->attributes = Yii::$app->request->post();
        $form->user = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$model = $form->updateTeacher()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher is not updated!');
        }

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

    public function actionDeleteAccount()
    {
        $user_id = Yii::$app->user->id;
        $model = User::find()->andWhere(['id' => $user_id])->andWhere(['type' => SharedConstant::TYPE_TEACHER])->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User record not found');
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
}

