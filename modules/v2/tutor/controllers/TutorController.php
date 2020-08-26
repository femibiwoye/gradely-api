<?php

namespace app\modules\v2\tutor\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\{ApiResponse, TutorProfile, TutorSession, Review};

class TutorController extends ActiveController
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
            'class' => HttpBearerAuth::className(),
            'except' => ['index', 'profile'],

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

    public function actionIndex()
    {
        $models = $this->modelClass::find()->where(['type' => SharedConstant::ACCOUNT_TYPE[4]])->all();
        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($models, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionProfile($tutor_id)
    {
        $model = TutorProfile::findOne(['tutor_id' => $tutor_id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Tutor record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Tutor record found');
    }

    public function actionTutorReview()
    {
        $type = Yii::$app->user->identity->type;
        if ($type == SharedConstant::TYPE_SCHOOL || $type == SharedConstant::TYPE_TEACHER) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission failed');
        }

        $session_id = Yii::$app->request->post('session_id');
        $rate = Yii::$app->request->post('rate');
        $sender_id = Yii::$app->request->post('sender_id');
        $receiver_id = Yii::$app->request->post('receiver_id');
        $form = new \yii\base\DynamicModel(compact('session_id', 'sender_id', 'rate', 'receiver_id'));
        $form->addRule(['session_id', 'sender_id', 'receiver_id', 'rate'], 'required');
        $form->addRule(['session_id', 'rate', 'sender_id', 'receiver_id'], 'integer');
        $form->addRule('session_id', 'exist', ['targetClass' => TutorSession::className(), 'targetAttribute' => ['session_id' => 'id']]);
        $form->addRule(['sender_id', 'receiver_id'], 'exist', ['targetClass' => $this->modelClass::className(), 'targetAttribute' => ['sender_id' => 'id']]);
        $form->addRule(['receiver_id'], 'exist', ['targetClass' => $this->modelClass::className(), 'targetAttribute' => ['receiver_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = new Review;
        $model->attributes = Yii::$app->request->post();
        if (!$model->save()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Review not made');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Review made');
    }
}

