<?php

namespace app\modules\v2\controllers;

use app\modules\v2\models\ApiResponse;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;


/**
 * Auth controller
 */
class ServicesController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];
        //$behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];

        return $behaviors;
    }

    /**
     * Returns cloudinary
     * @return ApiResponse
     */
    public function actionCloudinary()
    {
        $response = [
            'cloud_name' => 'gradely',
            'preset'=>'gradely',
            'cloudinary_base_delivery' => 'http://res.cloudinary.com/gradely',
            'cloudinary_base_api' => 'https://api.cloudinary.com/v1_1/gradely'
        ];

        return (new ApiResponse)->success($response);
    }
}

