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
            'cloudinary_api_key' => '596849949737384',
            'cloudinary_api_secret' => 'BUkJQPz2ZKtNdUt_v2UgSdQ-EBU',
            'cloudinary_base_delivery' => 'https://api.cloudinary.com/v1_1/gradely',
            'cloudinary_base_api' => 'https://api.cloudinary.com/v1_1/gradely'
        ];

        return (new ApiResponse)->success($response);
    }
}

