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
            'except' => ['ckeditor']
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
            'preset' => 'gradely',
            'cloudinary_base_delivery' => 'http://res.cloudinary.com/gradely',
            'cloudinary_base_api' => 'https://api.cloudinary.com/v1_1/gradely'
        ];

        return (new ApiResponse)->success($response);
    }

    public function actionCkeditor()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
       return  ["versionPlatform" => "unknown",
            "editorParameters" => [],
            "imageFormat" => "svg",
            "CASEnabled" => false,
            "parseModes" => [
                "latex"
            ],
            "editorToolbar" => "",
            "editorAttributes" => "width=570, height=450, scroll=no, resizable=yes",
            "base64savemode" => "default",
            "modalWindow" => true,
            "version" => "7.26.0.1439",
            "enableAccessibility" => true,
            "saveMode" => "xml",
            "saveHandTraces" => false,
            "editorUrl" => "https://gradly.s3.eu-west-2.amazonaws.com/assets/ckeditor/ckeditor.js",
            "editorEnabled" => true,
            "chemEnabled" => true,
            "CASMathmlAttribute" => "alt",
            "CASAttributes" => "width=640, height=480, scroll=no, resizable=yes",
            "modalWindowFullScreen" => false,
            "imageMathmlAttribute" => "data-mathml",
            "hostPlatform" => "unknown",
            "wirisPluginPerformance" => true
        ];
    }
}

