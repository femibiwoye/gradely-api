<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\Pricing;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Parents;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\SubscriptionChildren;
use app\modules\v2\models\SubscriptionPaymentDetails;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use app\paystack\Paystack\Routes\Subscription;
use Yii;
use yii\helpers\ArrayHelper;
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
        $form->addRule(['coupon'], 'exist', ['targetClass' => Coupon::className(), 'targetAttribute' => ['coupon' => 'code']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = Coupon::find()
            ->select(['code', 'percentage'])
            ->where(['code' => $coupon, 'status' => SharedConstant::VALUE_ONE, 'coupon_payment_type' => $type]);

        if ($model->one() && $model->one()->is_time_bound == 1) {
            $model = $model->andWhere(['<', 'start_time', time()])
                ->andWhere(['>', 'end_time', time()]);
        }
        $model = $model->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionPaymentPlans($type)
    {
        $form = new \yii\base\DynamicModel(compact('type'));
        $form->addRule(['type'], 'in', ['range' => ['catchup', 'tutor','bundle','summer']]);
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = PaymentPlan::find()->where(['type' => $type,'status'=>1])->limit(3)->all();
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

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Subscription updated');
    }

    public function actionSubscriptionPayment()
    {
        $type = Yii::$app->user->identity->type;
        $form = new PaymentSubscription(['scenario' => "$type-subscription"]);
        $form->attributes = Yii::$app->request->post();
        $form->user_id = Yii::$app->user->identity->id;
        if (Yii::$app->user->identity->type == SharedConstant::ACCOUNT_TYPE[3]) {
            $form->children = [Yii::$app->user->id];
        }

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if($form->type == 'tutor') {
            if (!$model = $form->addPaymentTutor()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subscription initialization failed');
            }
        }else{
            if (!$model = $form->addPaymentSubscription()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subscription initialization failed');
            }
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Subscription initialization done');
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }


        $model = Subscriptions::find()->where(['id' => $id])->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Payment not found!');
        }

        if (!$model->transaction_id) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Payment not made');
        }

        $message = $model->ConfirmPayment($model);

        return (new ApiResponse)->success($message['model'], ApiResponse::SUCCESSFUL, $message['message']);
    }

    public function actionCardDetails()
    {
        $parent_id = Yii::$app->user->id;

        $subscription = SubscriptionPaymentDetails::find()
            ->select(['brand', 'last4', 'channel'])
            ->where([
                'user_id' => $parent_id,
                'reusable' => SharedConstant::VALUE_ONE,
                'selected' => SharedConstant::VALUE_ONE
            ])
            ->asArray()->one();

        return $subscription;
    }

    public function actionChildrenSubscription()
    {

        if (Yii::$app->user->identity->type != 'parent')
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not have access');

        $parentChildren = Parents::find()->where(['parent_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE])->all();

        if (!$parentChildren)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not have children');


        $cardDetails = $this->actionCardDetails();
        $children = UserModel::find()
            ->select([
                'user.id',
                'firstname',
                'lastname',
                'image',
                'code',
                'class',
                'subscription_expiry',
                'subscription_plan',
                //'sc.*'
            ])
            ->where(['id' => ArrayHelper::getColumn($parentChildren, 'student_id')])
            //->innerJoin('subscription_children sc','sc.student_id = user.id')
            ->asArray()
            //->orderBy(['firstname'=>'ASC','sc.id'=>'DESC'])
            ->all();

        foreach ($children as $key => $child) {
            $childSub = SubscriptionChildren::find()->where(['student_id' => $child['id'], 'payment_status' => 'paid'])->orderBy('id DESC')->one();


            $children[$key] = array_merge(
                $children[$key],
                ['class' => Utility::StudentClassDetails($child['id'])],
                ['catchup' =>
                    ['status' => Utility::getSubscriptionStatus(User::findOne($child['id'])),
                        //'amount' => 1500,
                        'last_payment_status' => isset($childSub->payment_status) ? $childSub->payment_status : null,
                        'subscription_id' => isset($childSub->subscription) ? $childSub->subscription->id : null,
                        'price' => isset($childSub->subscription) ? $childSub->subscription->price : null,
                        'duration' => isset($childSub->subscription) ? $childSub->subscription->duration : null,
                        'duration_count' => isset($childSub->subscription) ? $childSub->subscription->duration_count : null,
                        'plan' => isset($childSub->subscription) ? $childSub->subscription->plan : null,
                    ]
                ],
                ['tutor' => null]
            );
        }

        return array_merge(['card' => $cardDetails], ['children' => $children]);
    }

    public function actionChildSubscription()
    {

        if (Yii::$app->user->identity->type != 'student')
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not have access');


        $cardDetails = $this->actionCardDetails();

        $childSub = SubscriptionChildren::find()->where(['student_id' => Yii::$app->user->id, 'payment_status' => 'paid'])->orderBy('id DESC')->one();


        $child =
            ['catchup' =>
                ['status' => Utility::getSubscriptionStatus(User::findOne(Yii::$app->user->id)),
                    //'amount' => 1500,
                    'last_payment_status' => isset($childSub->payment_status) ? $childSub->payment_status : null,
                    'price' => isset($childSub->subscription) ? $childSub->subscription->price : null,
                    'duration' => isset($childSub->subscription) ? $childSub->subscription->duration : null,
                    'duration_count' => isset($childSub->subscription) ? $childSub->subscription->duration_count : null,
                    'plan' => isset($childSub->subscription) ? $childSub->subscription->plan : null,


                ],
                'tutor' => null
            ];

        return ['card' => $cardDetails, 'child' => $child];

    }

    public function actionBillingHistory()
    {
        $subscription = Subscriptions::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy('id DESC')
            ->all();

        return (new ApiResponse)->success($subscription, ApiResponse::SUCCESSFUL);
    }

    public function actionSubscriptionStatus($child = null, $status = 1)
    {
        return (new ApiResponse)->success(Pricing::SubscriptionStatus(null, $child, $status == 1 ? true : false), ApiResponse::SUCCESSFUL);
    }
}
