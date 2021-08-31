<?php

namespace app\modules\v2\controllers;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\handler\ClickActionLogger;
use app\modules\v2\models\handler\ClickActionLoggerDetails;
use app\modules\v2\models\TeacherClass;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;


/**
 * Auth controller
 */
class HandlerController extends Controller
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


        return $behaviors;
    }

    /**
     * Taking log of clicks
     * @return ApiResponse
     */
    public function actionUserActionLogger()
    {
        $user_id = Yii::$app->request->post('user_id');
        $action_name = Yii::$app->request->post('action_name');
        $page_name = Yii::$app->request->post('page_name');
        $url = Yii::$app->request->post('url');

        $model = new \yii\base\DynamicModel(compact('page_name', 'action_name', 'url', 'user_id'));
        $model->addRule(['page_name', 'action_name', 'url'], 'string')
            ->addRule(['page_name', 'action_name'], 'required');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if ($handler = ClickActionLogger::find()->where(['action_name' => $action_name, 'page_name' => $page_name])->one()) {
            $handler->click_count++;
            $handler->save();
        } else {
            $handler = new ClickActionLogger();
            if (!empty($user_id)) {
                $handler->user_id = $user_id;
            }
            if (!empty($url)) {
                $handler->url = $url;
            }
            $handler->action_name = $action_name;
            $handler->page_name = $page_name;
        }
        $handler->save();


        $handlerDetail = new ClickActionLoggerDetails();
        if (!empty($user_id)) {
            $handlerDetail->user_id = $user_id;
        }
        if (!empty($url)) {
            $handler->url = $url;
        }
        $handlerDetail->action_name = $action_name;
        $handlerDetail->page_name = $page_name;
        if (!$handlerDetail->save()) {
            return (new ApiResponse)->error(false, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not be saved');
        }
        return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL);
    }
}

