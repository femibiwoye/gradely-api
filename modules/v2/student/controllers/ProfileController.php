<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\models\User;
use app\modules\v2\teacher\models\UpdateTeacherForm;
use Yii;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use app\modules\v2\models\{Parents, ApiResponse, InviteLog, StudentDetails};
use app\modules\v2\components\{CustomHttpBearerAuth, SharedConstant};
use app\modules\v2\student\models\{StudentUpdateEmailForm, StudentUpdatePasswordForm, UpdateStudentForm};
use yii\web\UploadedFile;

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

    public function actionParents()
    {
        $models = Parents::find()
            ->alias('a')
            ->select([
                'u.id',
                'u.firstname',
                'u.lastname',
                'u.image',
                'u.email',
                'u.phone',
                'a.role',
                'a.created_at',
            ])
            ->innerJoin('user u', 'u.id = a.parent_id')
            ->where(['student_id' => Yii::$app->user->id])
            ->asArray()
            ->all();

        if (!$models) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No parent available!');
        }

        return (new ApiResponse)->success($models, ApiResponse::SUCCESSFUL);

    }

    public function actionPendingParentInvitations()
    {
        $models = InviteLog::find()
            ->select([
                'receiver_name',
                'receiver_email',
                'receiver_phone',
                'receiver_type',
                'extra_data as role',
                'status',
                'created_at',
            ])
            ->where(['sender_id' => Yii::$app->user->id, 'sender_type' => 'student', 'status' => SharedConstant::VALUE_ZERO, 'receiver_type' => 'parent'])
            ->asArray()
            ->all();

        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($models, ApiResponse::SUCCESSFUL, 'Record found');

    }

    public function actionUpdateEmail()
    {
        $model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();

        $form = new StudentUpdateEmailForm();
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

        $form = new StudentUpdatePasswordForm();
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
        $model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id, 'type' => 'student'])->one();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student not found!');
        }

        $form = new UpdateStudentForm;
        $form->attributes = Yii::$app->request->post();
        $form->user = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model = $form->updateStudent()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student is not updated!');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionReport()
    {
        $model = StudentDetails::findOne(['id' => Yii::$app->user->id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student report not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Student report found');
    }

    public function actionDelete() {

        $student_id = Yii::$app->user->id;

        $model = User::find()
                   ->andWhere(['id' => $student_id])
                    ->andWhere(['type' => SharedConstant::TYPE_STUDENT])->one();
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

    public function actionUpdateAvatar(){

        $form = new UpdateStudentForm();
        $form->image = Yii::$app->request->post();

        $img = UploadedFile::getInstance($this->user, 'image');
        $imageName = 'user_' . $this->user->id . '.' . $img->getExtension();

        $user = User::findOne(Yii::$app->user->id);

        if($img->saveAs(Yii::getAlias('@webroot') . '/images/users/' . $imageName)){

            if($user->image){

                unlink(\Yii::getAlias('@webroot') . '/images/users/' . $user->image);

                $user->image =$imageName;
                $user->save();
            }

            return (new ApiResponse)->error($user, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Avatar Updated!');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'User not found');



    }
}

