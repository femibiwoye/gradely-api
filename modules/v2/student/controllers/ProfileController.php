<?php

namespace app\modules\v2\student\controllers;

use Yii;
use yii\rest\ActiveController;
use app\modules\v2\models\{Parents, ApiResponse, InviteLog, StudentDetails};
use app\modules\v2\components\{CustomHttpBearerAuth, SharedConstant};
use app\modules\v2\student\models\{StudentUpdateEmailForm, StudentUpdatePasswordForm, UpdateStudentForm};

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
					->where(['student_id' => Yii::$app->user->id])
					->all();

		if (!$models) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Parents record not found');
		}

		return (new ApiResponse)->success($models, ApiResponse::SUCCESSFUL, 'Parents record found');
	}

	public function actionPendingParentInvitations()
	{
		$models = InviteLog::find()
					->where(['sender_id' => Yii::$app->user->id, 'sender_type' => 'student', 'status' => SharedConstant::VALUE_ZERO, 'receiver_type' => 'teacher'])
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
}

