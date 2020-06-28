<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\SignupForm;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};


/**
 * Auth controller
 */
class AuthController extends Controller
{
    public $modelClass = 'app\modules\v2\models\User';

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

   public function actionUpdateBoarding()
   {

   }
}

