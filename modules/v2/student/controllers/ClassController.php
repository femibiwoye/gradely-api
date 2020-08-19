<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use Yii;
use yii\rest\ActiveController;
use app\modules\v2\models\{Classes, ApiResponse, StudentSchool};


/**
 * Schools/Parent controller
 */
class ClassController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Classes';

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

    public function actionVerifyClass($code)
    {
        $class = $this->modelClass::find()
            ->alias('c')
            ->select([
                'c.*',
                's.name school_name'
            ])
            ->innerJoin('schools s', 's.id = c.school_id')
            ->where(['class_code' => $code])
            ->asArray()->one();
        if (!$class) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found!');
        }

        return (new ApiResponse)->success($class, ApiResponse::SUCCESSFUL, 'Class found');
    }

    public function actionStudentClass()
    {
        if (!Yii::$app->request->post('code')) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class code is required');
        }

        if (StudentSchool::find()->where(['student_id' => Yii::$app->user->id, 'status' => 1])->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student already in class');
        }

        $class = Classes::findOne(['class_code' => Yii::$app->request->post('code')]);
        if (!$class) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model = new StudentSchool;
        $model->student_id = Yii::$app->user->id;
        $model->school_id = $class->school_id;
        $model->class_id = $class->id;
        $model->invite_code = Yii::$app->request->post('code');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not validated');
        }
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student not joined saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Student joined the class');
    }

}