<?php

namespace app\modules\v2\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\{ApiResponse, Coupon, PaymentPlan};

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
        $type = Yii::$app->request->post('type');

        $form = new \yii\base\DynamicModel(compact('coupon', 'type'));
        $form->addRule(['coupon', 'type'], 'required');
        $form->addRule(['coupon'], 'exist', ['targetClass' => Coupon::className(), 'targetAttribute' => ['coupon' => 'code']]);

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
}
