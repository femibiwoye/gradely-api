<?php


namespace app\modules\v2\tutor\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\GlobalClass;
use app\modules\v2\models\{ApiResponse, PaymentPlan, Coupon, Subjects, TutorSession, TutorSessionTiming};
use app\modules\v2\models\User;
use app\modules\v2\components\Utility;

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

    /**
     * Booking a tutor for one time session
     */
    public function actionSingleBooking()
    {
        $tutor_id = Yii::$app->request->post('tutor_id');
        $subject_id = Yii::$app->request->post('subject_id');
        $availability = Yii::$app->request->post('availability');
        $student_id = Yii::$app->request->post('student_id');
        $payment_plan_id = Yii::$app->request->post('payment_plan_id');
        $coupon = Yii::$app->request->post('coupon');
        $session_count = Yii::$app->request->post('session_count');

        $validate = new \yii\base\DynamicModel(compact('tutor_id', 'subject_id', 'availability','student_id','payment_plan_id','session_count'));
        $validate->addRule(['tutor_id', 'subject_id', 'availability','student_id','payment_plan_id','session_count'],'required');
        $validate
            ->addRule(['tutor_id'], 'exist', ['targetClass' => User::className(), 'targetAttribute' => ['tutor_id'=>'id']]);
        $validate->addRule(['student_id'], 'exist', ['targetClass' => User::className(), 'targetAttribute' => ['student_id'=>'id']]);
        $validate->addRule(['subject_id'], 'exist', ['targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id'=>'id']]);
        $validate->addRule(['payment_plan_id'], 'exist', ['targetClass' => PaymentPlan::className(), 'targetAttribute' => ['payment_plan_id'=>'id']]);
        if (!$validate->validate()) {
            return (new ApiResponse)->error($validate->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $payment_plan = PaymentPlan::findOne(['id' => $payment_plan_id]);
        $price = $payment_plan->price;
        $curriculum = $payment_plan->curriculum;
        $repetition = $payment_plan->slug;

        $total = $price * $session_count;

        foreach ($availability as $available) {
             $date = $available['date'];
             $time = $available['time'];
        }
        
        $payment_plan = PaymentPlan::findOne(['id' => $payment_plan_id, 'type'=>'tutor']);
        $price = $payment_plan->price;
        $curriculum = $payment_plan->curriculum;
        $repetition = $payment_plan->slug;

        $subject = Subjects::findOne(['id' => $subject_id]);
        $title = $subject->slug;

        $model = new TutorSession();
        $model->requester_id = $tutor_id;
        $model->student_id = $student_id;
        $model->subject_id = $subject_id;
        $model->availability = $date .' '. $time;
        $model->title = $title;
        $model->session_count = $session_count;
        $model->repetition = $repetition;
        $model->class = Utility::getStudentClass($student_id);
        $model->category = 'Tutor';


        $timemodel = new TutorSessionTiming();
        $timemodel->session_id = $model->id;
        $timemodel->day = $date;
        $timemodel->time = $time;
        $timemodel->save();
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not saved');
        }
            
            return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'TutorSession saved');

        if ($coupon) {
            $couponcode = Coupon::findOne(['code' => $coupon]);
            if ($couponcode) {
                $discount = Coupon::findOne(['status' => 1]);
                $percentage = $discount->percentage;
                if ($discount) {
                    $newprice = ($price * $session_count);
                    $discountedPrice = ($percentage / 100) * $newprice;
                    $discountedPrice = ($newprice - $discountedPrice);
                    return (new ApiResponse)->success($discountedPrice, ApiResponse::SUCCESSFUL, 'Discount');
                }
            }
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Incorrect Coupon');
        }

        return (new ApiResponse)->success($total, ApiResponse::SUCCESSFUL, 'Total Price');


    }

    public function actionSingleBookinBK()
    {
        $tutor_id = Yii::$app->request->post('tutor_id');
        $subject_id = Yii::$app->request->post('subject_id');
        $availability = Yii::$app->request->post('availability');
        $student_id = Yii::$app->request->post('student_id');
        $payment_plan_id = Yii::$app->request->post('payment_plan_id');
        $coupon = Yii::$app->request->post('coupon');
        $session_count = Yii::$app->request->post('session_count');


        $payment_plan = PaymentPlan::findOne(['id' => $payment_plan_id]);
        $price = $payment_plan->price;
        $curriculum = $payment_plan->curriculum;
        $repetition = $payment_plan->slug;

        $subject = Subjects::findOne(['id' => $subject_id]);
        $title = $subject->slug;

        $total = $price * $session_count;
        foreach ($availability as $available) {
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


        if ($coupon) {
            $couponcode = Coupon::findOne(['code' => $coupon]);
            if ($couponcode) {
                $discount = Coupon::findOne(['status' => 1]);
                $percentage = $discount->percentage;
                if ($discount) {
                    $newprice = ($price * $session_count);
                    $discountedPrice = ($percentage / 100) * $newprice;
                    $discountedPrice = ($newprice - $discountedPrice);
                    return (new ApiResponse)->success($discountedPrice, ApiResponse::SUCCESSFUL, 'Discount');
                }
            }
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Incorrect Coupon');
        }

        return (new ApiResponse)->success($total, ApiResponse::SUCCESSFUL, 'Total Price');
    }

}
