<?php

namespace app\modules\v2\controllers;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\FeatureUserLogger;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;


/**
 * Auth controller
 */
class FeatureUserLoggerController extends Controller
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
    public function actionIndex()
    {
       /// $model = ['name'=>'welcome_school','type'=>'school']; //Temporary solution

        $model = FeatureUserLogger::find()->where(['user_id' => Yii::$app->user->id])->select(['name', 'type'])->all();
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL);
    }

    public function actionUpdate($name, $type)
    {
        $user = Yii::$app->user;
        if (!FeatureUserLogger::find()->where(['name' => $name, 'user_id' => $user->id, 'type' => ['all', $user->identity->type]])->exists()) {
            $model = new FeatureUserLogger();
            $model->user_id = $user->id;
            $model->type = $type;
            $model->name = $name;
            if (!$model->save()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Cannot process');
            }
        }
        return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL);
    }
}

