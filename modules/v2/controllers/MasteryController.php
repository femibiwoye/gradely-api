<?php

namespace app\modules\v2\controllers;

use app\modules\v2\models\ApiResponse;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;


/**
 * Auth controller
 */
class MasteryController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];

        return $behaviors;
    }

    public function actionStudentMastery()
    {
        
    }
}

