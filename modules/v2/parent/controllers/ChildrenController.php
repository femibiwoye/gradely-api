<?php

namespace app\modules\v2\parent\controllers;

use Yii;
use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\Utility;
use app\modules\v2\components\SharedConstant;
//models
use app\modules\v2\models\{parents, ApiResponse, User};


use yii\db\Expression;
use yii\filters\AccessControl;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;

/**
 * module parent/Children controller
 */
class ChildrenController extends ActiveController
{
    public $modelClass='app\modules\v2\models\Parent';

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

        //Control user type that can access this
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        return Yii::$app->user->identity->type == 'parent';
                    },
                ],
            ],
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

    public function actionList()
    {
        $parent_id =  Yii::$app->user->id;
        $students = parents::find()->where(['parent_id' => $parent_id])->all();

        foreach ($students as $k=> $student) {
            $students[$k]= User::find()->where(['id'=>$student->student_id])->one();
        }

        if (!$students) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No Parent information available!');
        }

        return (new ApiResponse)->success($students, ApiResponse::SUCCESSFUL);
    }

}
