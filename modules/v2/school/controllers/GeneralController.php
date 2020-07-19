<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\SchoolNamingFormat;
use app\modules\v2\models\SchoolType;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;


/**
 * Schools/Parent controller
 */
class GeneralController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\SchoolType';
    //public $modelFormat = 'app\modules\v2\models\SchoolNamingFormat';

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
            'class' => HttpBearerAuth::className(),
        ];

        //Control user type that can access this
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        return Yii::$app->user->identity->type == 'school';
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


    public function actionSchoolType()
    {
        return (new ApiResponse)->success(SchoolType::find()->where(['status'=>1])->all(), ApiResponse::SUCCESSFUL, 'Found');
    }

    public function actionSchoolNamingFormat()
    {
        return (new ApiResponse)->success(SchoolNamingFormat::find()->where(['status'=>1])->all(), ApiResponse::SUCCESSFUL, 'Found');
    }


}