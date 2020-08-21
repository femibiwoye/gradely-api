<?php

namespace app\modules\v2\parent\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\models\HomeworkReport;
use app\modules\v2\models\Parents;
use app\modules\v2\models\{Homeworks, ApiResponse};

use app\modules\v2\student\models\StudentHomeworkReport;
use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use app\modules\v2\components\SharedConstant;


/**
 * Schools/Parent controller
 */
class HomeworkController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Homeworks';

    /**
     * @return array
     */
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
            'class' => CustomHttpBearerAuth::className(),
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


    public function actionHomeworkScore($homework_id){


        $parent = Parents::find()->where(['user_id' => Yii::$app->user->id])->all();

        $homework = HomeworkReport::find()
                    ->innerJoin('quiz_summary summary', 'summary.homework_id = homeworks.id')
                    ->andWhere([
                        'homeworks.id' => $homework_id,
                        'homeworks.publish_status' => 1,
                    ])
                    ->andWhere(['in', 'student_id', $parent])
                    ->all();

        if(!$homework){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not found!');
        }

        return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homework Score succcessfully retrieved');
    }
}