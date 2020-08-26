<?php

namespace app\modules\v2\student\controllers;

use app\modules\v1\models\StudentSchool;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Homeworks;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;

class PracticeController extends Controller
{

    public $modelClass = 'app\modules\v2\models\PracticeTopics';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        //Add CORS Filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }


    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['view']);
        return $actions;
    }

    public function actionHomeworkInstruction($homework_id){

        $studentClass = StudentSchool::findOne(['student_id' => \Yii::$app->user->id]);

        $homework = Homeworks::find()
            ->where(['id'=>$homework_id,'class_id'=>$studentClass->class_id])
            ->one();

        if(!$homework){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No homework found!');
        }

        return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homework retrieved');

    }
}
