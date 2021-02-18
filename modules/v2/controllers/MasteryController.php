<?php

namespace app\modules\v2\controllers;


use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\StudentMastery;
use app\modules\v2\models\StudentTopicPerformance;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;


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

    public function actionTopics()
    {
        $user = Yii::$app->user->identity;
        if ($user->type != 'student' && $user->type != 'parent') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $model = new StudentMastery();
        $model->student_id = Utility::getParentChildID();
        $model->term = Yii::$app->request->get('term');
        $model->class = Yii::$app->request->get('class');
        $model->subject = Yii::$app->request->get('subject');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        return (new ApiResponse)->success($model->getPerformance(), ApiResponse::SUCCESSFUL, 'Topic performance');
    }

}

