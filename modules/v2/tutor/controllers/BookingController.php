<?php

namespace app\modules\v2\tutor\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\GlobalClass;
use app\modules\v2\models\{ApiResponse, PaymentPlan, Coupon, Subjects, TutorSession,};

class BookingController extends ActiveController
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

    public function actionSingleBooking(){
        $tutor_id = Yii::$app->request->post('tutor_id');
        $subject_id = Yii::$app->request->post('subject_id');
        $availability = Yii::$app->request->post('availability');
        $student_id = Yii::$app->request->post('student_id');
        $payment_plan_id = Yii::$app->request->post('payment_plan_id');
        $coupon = Yii::$app->request->post('coupon');
        $session_count = Yii::$app->request->post('session_count');

    
        $payment_plan= PaymentPlan::findOne(['id' => $payment_plan_id]);
        $price= $payment_plan->price;
        $curriculum = $payment_plan->curriculum;
        $repetition = $payment_plan->slug;

        $subject = Subjects::findOne(['id'=>$subject_id]);
        $title = $subject->slug;

        $total = $price * $session_count;
        foreach($availability as $available){
             implode($available);
        }   
       

        $model = new TutorSession();
        $model->requester_id = $tutor_id;
        $model->student_id = $student_id;
        $model->subject_id = $subject_id;
        $model->availability = $available; 
        $model->title = $title;
        //$model->curriculum_id = $curriculum;
        $model->repetition = $repetition;
        //$model->class = $this->getStudentClass();
        $model->category = 'Tutor';
        
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not saved');
        }
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record saved');

        
        if($coupon){
            $couponcode = Coupon::findOne(['code' => $coupon]);
            if($couponcode){
                $discount = Coupon::findOne(['status' => 1]);
                $percentage = $discount->percentage;
                    if($discount){
                    $newprice = ($price * $session_count);
                    $discountedPrice = ($percentage/100)*$newprice;
                    $discountedPrice = ($newprice - $discountedPrice);
                    return (new ApiResponse)->success($discountedPrice, ApiResponse::SUCCESSFUL, 'Discount');
                }
            }
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Incorrect Coupon');
        }
        
        return (new ApiResponse)->success($total, ApiResponse::SUCCESSFUL, 'Total Price');
    } 

}?>