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


class ClassController extends ActiveController
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


    public function actionMapClassSubject()
    {

        $subject_id = Yii::$app->request->post('subject_id');
        $class_id = Yii::$app->request->post('class_id');
        $model = new \yii\base\DynamicModel(compact('class_id', 'subject_id'));
        $model->addRule(['class_id', 'subject_id'], 'required');
        $model->addRule(['subject_id'], 'exist', ['targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!is_array($class_id)) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Class id must be an array');
        }

        $school = Schools::findOne(['id' => SmsAuthentication::checkStatus()]);


        if (ClassSubjects::find()->where(['class_id' => $class_id, 'school_id' => $school->id, 'subject_id' => $subject_id, 'status' => 1])->count() >= $class_id) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'No new record to be added');
        }

        $classes = Classes::find()->where(['id' => $class_id, 'school_id' => $school->id])->all();
        if (count($classes) < 1) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'No class selected');
        }

        $n = 0;
        foreach ($classes as $class) {
            if (ClassSubjects::find()->where(['class_id' => $class, 'school_id' => $school->id, 'subject_id' => $subject_id, 'status' => 1])->exists()) {
                continue;
            }
            $model = new ClassSubjects();
            $model->class_id = $class->id;
            $model->school_id = $school->id;
            $model->subject_id = $subject_id;
            $model->status = 1;
            if ($model->save())
                $n++;
        }

        if ($n > 0) {
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, $n . ' saved!');
        } else {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No new record');
        }

    }

    public function actionGetClassSubjects($class_id)
    {
        $school = Schools::findOne(['id' => SmsAuthentication::checkStatus()]);
        $class = ClassSubjects::find()->where(['class_id' => $class_id, 'school_id' => $school->id, 'status' => 1])->all();
        return (new ApiResponse)->success($class, ApiResponse::SUCCESSFUL);
    }

    /** Create a single class
     * @return ApiResponse
     */
    public function actionCreateClassArm()
    {

        $school = Schools::findOne(['id' => SmsAuthentication::checkStatus()]);

        $form = new ClassForm(['scenario' => ClassForm::SCENERIO_CREATE_CLASS]);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$model = $form->newClass($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class is not updated');
        }

        return (new ApiResponse)->success($model);
    }


    public function actionUpdateClassArm()
    {
        $form = new ClassForm(['scenario' => ClassForm::SCENERIO_UPDATE_CLASS]);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $school = Schools::findOne(['id' => SmsAuthentication::checkStatus()]);
        $classModel = Classes::find()->where(['school_id' => $school->id, 'id' => $form->id]);
        if (!$classModel->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'This is not a valid class!');
        }

        $model = $classModel->one();
        $model->class_name = $form->class_name;
        $model->save();

        return (new ApiResponse)->success($model, null, 'Class successfully updated.');
    }

    public function actionGetGlobalClassArms($class_id)
    {
        $school = Schools::findOne(['id' => SmsAuthentication::checkStatus()]);
        $classModel = Classes::find()->where(['school_id' => $school->id, 'global_class_id' => $class_id])->all();

        return (new ApiResponse)->success($classModel);
    }
}