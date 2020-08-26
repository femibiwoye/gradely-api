<?php

namespace app\modules\v2\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\{ApiResponse, Coupon, PaymentPlan, Subscriptions, PaymentSubscription};

class PaymentController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Coupon';

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

    public function actionVerifyCoupon()
    {
        if (Yii::$app->user->identity->type == SharedConstant::ACCOUNT_TYPE[0] || Yii::$app->user->identity->type == SharedConstant::ACCOUNT_TYPE[1]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $coupon = Yii::$app->request->post('coupon');
        $type = ['all', Yii::$app->request->post('type')];

        $form = new \yii\base\DynamicModel(compact('coupon', 'type'));
        $form->addRule(['coupon', 'type'], 'required');
        $form->addRule(['coupon'], 'exist', ['targetClass' => Coupon::className(), 'targetAttribute' => ['coupon' => 'code','type']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = Coupon::find()
            ->select(['code', 'percentage'])
            ->where(['code' => $coupon, 'status' => SharedConstant::VALUE_ONE, 'type' => $type])
            ->andWhere(['<', 'start_time', time()])
            ->andWhere(['>', 'end_time', time()])
            ->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionPaymentPlans($type)
    {
        $form = new \yii\base\DynamicModel(compact('type'));
        $form->addRule(['type'], 'in', ['range' => ['catchup', 'tutor']]);
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = PaymentPlan::find()->where(['type' => $type])->all();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionCancelSubscription($subscription_id)
    {
        $model = Subscriptions::findOne(['id' => $subscription_id, 'user_id' => Yii::$app->user->id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subscription not found');
        }

        if ($model->renew_status == SharedConstant::VALUE_ZERO) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subscription already disabled');
        }

        $model->renew_status = SharedConstant::VALUE_ZERO;
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subscription not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Subscription updated');
    }

    public function actionSubscriptionPayment()
    {
        $form = new PaymentSubscription;
        $form->attributes = Yii::$app->request->post();
        $form->user_id = Yii::$app->user->identity->id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$model = $form->addPaymentSubscription()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subscription payment failed');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Subscription payment done');
    }

    public function actionPaymentStatus($id)
    {
        $form = new \yii\base\DynamicModel(compact('id'));
        $form->addRule(['id'], 'required');
        $form->addRule('id', 'exist', [
            'targetClass' => Subscriptions::className(),
            'targetAttribute' => 'id',
            'message' => 'Payment Id is invalid',
        ]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }


        $model = Subscriptions::find()->where(['id' => $id])->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Payment not found!');
        }

        $message = $model->ConfirmPayment($model);

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, $message->message);
    }


}
