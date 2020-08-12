<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Homeworks;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentDetails;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\User;
use Yii;
use app\modules\v2\components\{SharedConstant, Utility};
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


/**
 * Teacher controller
 */
class StudentController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\UserModel';

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
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
    }




    public function actionStudentClassHomework($student_id) {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('student_id', 'school_id'));
        $model->addRule(['student_id'], 'exist', ['targetClass' => StudentSchool::className(), 'targetAttribute' => ['student_id' => 'student_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $classes = Classes::find()
            ->innerJoin('student_school', 'student_school.class_id = classes.id')
            ->where(['student_school.student_id' => $student_id]);


        if (!$classes->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes not found!');
        }

        return (new ApiResponse)->success($classes->all(), ApiResponse::SUCCESSFUL, 'Classes found');
    }

    public function actionStudentHomework($student_id) {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;

        $homeworks = Homeworks::find()->where(['student_id' => $student_id])->all();
        if (!$homeworks) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homeworks not found!');
        }

        return (new ApiResponse)->success($homeworks, ApiResponse::SUCCESSFUL, 'Homeworks found');
    }

    public function actionProfile($student_id)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('student_id', 'school_id'));
        $model->addRule(['student_id'], 'exist', ['targetClass' => StudentSchool::className(), 'targetAttribute' => ['student_id' => 'student_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION,'This student does not belong to your school');
        }

        $detail = StudentDetails::findOne(['id'=>$student_id]);
        return (new ApiResponse)->success($detail);
    }


}
