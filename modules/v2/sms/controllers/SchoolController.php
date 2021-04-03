<?php

namespace app\modules\v2\sms\controllers;

use app\modules\v2\components\SmsAuthentication;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\ClassSubjects;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolSubject;
use app\modules\v2\models\SchoolTeachers;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\TeacherClassSubjects;
use app\modules\v2\school\models\ClassForm;
use Yii;
use yii\rest\ActiveController;


class SchoolController extends ActiveController
{
    public $modelClass = 'app\modules\v2\sms\models\Schools';

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

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
    }

    public function beforeAction($action)
    {
        if (!SmsAuthentication::checkStatus()) {
            $this->asJson(\Yii::$app->params['customError401']);
            return false;
        }
        return parent::beforeAction($action);
    }

}