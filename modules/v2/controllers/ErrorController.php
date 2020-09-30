<?php

namespace app\modules\v2\controllers;

use app\modules\v2\models\{ApiResponse, WebsiteError};
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;


/**
 * Auth controller
 */
class ErrorController extends Controller
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

    public function actionWebsiteError()
    {
        $model = new WebsiteError;
        $model->attributes = Yii::$app->request->post();
        $model->user = Yii::$app->user->id . ' - ' . Yii::$app->user->identity->type;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Website Error not saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Website error saved');
    }
}

