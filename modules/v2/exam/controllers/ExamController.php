<?php

namespace app\modules\v2\exam\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\Utility;
use app\modules\v2\exam\components\ExamUtility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\Subjects;
use Yii;
use yii\filters\AccessControl;
use yii\rest\ActiveController;


/**
 * Schools/Parent controller
 */
class ExamController extends ActiveController
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

        //Control user type that can access this
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        return in_array(Yii::$app->user->identity->type,['parent','student']);
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
      $category = ExamUtility::StudentClassCategory(Utility::ParentStudentChildClass(Utility::getParentChildID()));
        $model = ExamType::find()->where(['is_exam'=>1,'class'=>$category])->select(['name','slug','title','description'])->all();
        return (new ApiResponse)->success($model);
    }

    public function actionSubject()
    {
        $category = ExamUtility::StudentClassCategory(Utility::ParentStudentChildClass(Utility::getParentChildID()));
        $model = Subjects::find()->where(['school_id'=>null,'category'=>['all',$category]])->all();
        return (new ApiResponse)->success($model);
    }


}