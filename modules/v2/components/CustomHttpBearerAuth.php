<?php


namespace app\modules\v2\components;

use yii\filters\auth\HttpBearerAuth;
use Yii;

class CustomHttpBearerAuth extends HttpBearerAuth
{
    public function beforeAction($action)
    {
        parent::beforeAction($action);

//        if (Yii::$app->user->identity->type == SharedConstant::TYPE_SCHOOL)
//
//            date_default_timezone_set('America/Los_Angeles');
//        if (in_array('Africa/Lagos', \DateTimeZone::listIdentifiers())) {
//            echo "valid";
//        }
//        else {
//            echo "invalid";
//        }
        //Yii::$app->timeZone //('Africa/Lagos');
//        if (!Yii::$app->user->isGuest) {
//            $user = Yii::$app->user->identity;
//            if($user->type = 'school')
//                die;
//            //
//            // Yii::$app->setTimeZone('Africa/Lagos');
//        }


        return true;
    }
}