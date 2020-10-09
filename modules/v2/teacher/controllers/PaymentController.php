<?php

namespace app\modules\v2\teacher\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\{ApiResponse, Subscriptions};
use app\modules\v2\teacher\models\TutorPaymentForm;

class PaymentController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Subscriptions';

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
                HttpBearerAuth::className(),
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

    public function actionTutorPayment()
    {
        $form = new TutorPaymentForm;
        $form->attributes = Yii::$app->request->post();
        $form->reference_id = Yii::$app->request->post('tutor_id');
        $form->children = Yii::$app->request->post('student_id');
        $form->user_id = Yii::$app->user->id;
        $form->payment_plan_id = Yii::$app->request->post('plan_id');
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$model = $form->addPaymentSubscription()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher payments failed');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Teacher payments done');
    }

    public function actionSearchTutor($tutor_id)
    {
        $model = Subscriptions::findOne(['reference_id' => $tutor_id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Tutor record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Tutor record found');
    }
}
