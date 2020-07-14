<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Parents;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use app\modules\v2\teacher\models\ClassForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


/**
 * Schools/Parent controller
 */
class ClassesController extends ActiveController
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

    public function actionIndex()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);


        $getAllClasses = Classes::find()
            ->select([
                'classes.id',
                'classes.slug',
                'class_code',
                'class_name',
                'abbreviation',
                'global_class_id',
                'school_id',
                'schools.name school_name'

            ])
            ->leftJoin('schools', 'schools.id = classes.school_id')
            ->where(['school_id' => $school->id])
            ->asArray();


        if (!$getAllClasses->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No classes available!');
        }
        return (new ApiResponse)->success($getAllClasses->all(), ApiResponse::SUCCESSFUL, $getAllClasses->count() . ' classes found');
    }

    public function actionView($id)
    {
        $getClass = Classes::find()
            ->where(['school_id' => Utility::getSchoolAccess(), 'classes.id' => $id])
            ->joinWith(['school', 'globalClass'])
            ->asArray()
            ->one();
        if ($getClass) {
            return (new ApiResponse)->success($getClass, ApiResponse::SUCCESSFUL, 'Class found');
        }

        return (new ApiResponse)->success(null, ApiResponse::NOT_FOUND, 'Class not found!');
    }

    public function actionCreate()
    {

        return Utility::getSchoolAccess();
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $form = new ClassForm(['scenario' => ClassForm::SCENERIO_CREATE_CLASS]);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model = $form->newClass($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher is not updated!');
        }

        return (new ApiResponse)->success($model);






        $model = $this->modelClass::find()->andWhere(['id' => Yii::$app->user->id])->one();
        if ($model->type != SharedConstant::TYPE_TEACHER || !$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher not found!');
        }

        $form = new UpdateTeacherForm;
        $form->attributes = Yii::$app->request->post();
        $form->user = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model = $form->updateTeacher()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher is not updated!');
        }

        return (new ApiResponse)->success($model);



        $classes = new Classes(['scenario' => Classes::SCENERIO_CREATE_CLASS]);
        $classes->attributes = \Yii::$app->request->post();

        return $classes->attributes;

        if ($classes->validate()) {
            $classes->school_id = Utility::getSchoolId();
            $classes->global_class_id = $this->request['global_class_id'];
            $classes->class_name = $this->request['class_name'];
            $classes->class_code = $this->request['class_code'];
            $classes->slug = \yii\helpers\Inflector::slug($this->request['class_name']);
            $classes->abbreviation = Utility::abreviate($classes->slug);

            if ($classes->save()) {
                return[
                    'code' => 200,
                    'message' => "Successfully created"
                ];
            }

            $classes->validate();
            Yii::info('[Class generated succesfully] Error:'.$classes->validate().'');
            return $classes;
        }
        return $classes->errors;
    }

    public function actionUpdateClass($id)
    {

        $classes = new Classes(['scenario' => Classes::SCENERIO_UPDATE_CLASS]);
        $classes->attributes = \Yii::$app->request->post();
        if ($classes->validate()) {

            $getClass = Classes::find()->where(['id' => $id])->one();
            if (!empty($getClass)) {

                $getClass->global_class_id = $this->request['global_class_id'];
                $getClass->class_name = $this->request['class_name'];
                $getClass->abbreviation = $this->request['class_code'];

                try {

                    $getClass->save();
                    Yii::info('[Class update successful] school_id:' . $id . '');
                    return [
                        'code' => '200',
                        'message' => "Class update succesful"
                    ];
                } catch (Exception $exception) {
                    Yii::info('[Class update successful] ' . $exception->getMessage());
                    return [
                        'code' => '500',
                        'message' => $exception->getMessage()
                    ];
                }
            }

            Yii::info('[class does not exist] Class ID:' . $id);
            return [
                'code' => 200,
                'message' => 'class does not exist'
            ];
        }
        return $classes->errors;
    }


}