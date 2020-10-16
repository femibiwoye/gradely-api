<?php

namespace app\modules\v2\controllers;

use app\modules\v2\models\ApiResponse;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
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

    public function actionVerifyFile()
    {
        $credentials = new Credentials('AKIAIDY4XPIYLJBZ22DA','XAD60Ebp/nR99OEMx6Psr5zMejUs45SN12c306SN');


        $s3 = new S3Client([
            'version'     => 'latest',
            'region'      => 'eu-west-2',
            'credentials' => $credentials
        ]);

        return $s3->doesObjectExist('gradelyng','https://gradelyng.s3.us-east-2.amazonaws.com/avatars/avatar10_q0ocne.png');
    }
}

