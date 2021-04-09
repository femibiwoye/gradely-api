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
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\TeacherClassSubjects;
use Yii;
use yii\rest\ActiveController;


class TeacherController extends ActiveController
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


    //Remove teacher from class
    public function actionRemoveClassTeacher()
    {
        $class_id = Yii::$app->request->post('class_id');
        $teacher_id = Yii::$app->request->post('teacher_id');
        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id', 'teacher_id'));
        $model->addRule(['class_id', 'school_id', 'teacher_id'], 'required');
        $model->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id', 'school_id' => 'school_id']]);
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['teacher_id', 'school_id', 'class_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = TeacherClass::findOne(['school_id' => $school_id, 'class_id' => $class_id, 'teacher_id' => $teacher_id]);

        if ($model->delete()) {
            TeacherClassSubjects::deleteAll(['teacher_id' => $model->teacher_id, 'class_id' => $model->class_id, 'school_id' => $model->school_id]);
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Successful');
        }

        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not remove teacher from class');
    }

    //Remove teacher from school
    public function actionRemoveTeacherSchool()
    {
        $teacher_id = Yii::$app->request->post('teacher_id');
        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('school_id', 'teacher_id'));
        $model->addRule(['school_id', 'teacher_id'], 'required');
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => SchoolTeachers::className(), 'targetAttribute' => ['teacher_id', 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = SchoolTeachers::findOne(['school_id' => $school_id, 'teacher_id' => $teacher_id]);

        if ($model->delete()) {
            TeacherClass::deleteAll(['teacher_id' => $model->teacher_id, 'school_id' => $model->school_id]);
            TeacherClassSubjects::deleteAll(['teacher_id' => $model->teacher_id, 'school_id' => $model->school_id]);
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Successful');
        }

        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not remove teacher from class');
    }

    /**
     * Remove subject from teacher in a class.
     * @return ApiResponse
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionRemoveTeacherSubject()
    {
        $teacher_id = Yii::$app->request->post('teacher_id');
        $class_id = Yii::$app->request->post('class_id');
        $subject_id = Yii::$app->request->post('subject_id');
        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('school_id', 'teacher_id', 'class_id', 'subject_id'));
        $model->addRule(['school_id', 'teacher_id', 'class_id', 'subject_id'], 'required');
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['teacher_id', 'school_id', 'class_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (TeacherClassSubjects::deleteAll(['school_id' => $school_id, 'teacher_id' => $teacher_id, 'class_id' => $class_id, 'subject_id' => $subject_id])) {
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Successful');
        }

        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not remove subject from teacher');
    }

    //Assign subjects only to teacher in a class
    public function actionAssignSubjectToTeacher()
    {
        $subjects = Yii::$app->request->post('subjects');
        $class_id = Yii::$app->request->post('class_id');
        $teacher_id = Yii::$app->request->post('teacher_id');
        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id', 'subjects', 'teacher_id'));
        $model->addRule(['class_id', 'school_id', 'subjects', 'teacher_id'], 'required');
        $model->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id', 'school_id' => 'school_id']]);
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => SchoolTeachers::className(), 'targetAttribute' => ['teacher_id', 'school_id']]);
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!is_array($subjects)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'subjects must be an array');
        }

        if (count($subjects) > SchoolSubject::find()->where(['subject_id' => $subjects, 'school_id' => $school_id])->count()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'One or more subject is not in your subject list.');
        }

        $count = 0;
        foreach ($subjects as $subject) {
            if (TeacherClassSubjects::find()->where(['class_id' => $class_id, 'subject_id' => $subject, 'teacher_id' => $teacher_id])->exists())
                continue;

            $model = new TeacherClassSubjects();
            $model->class_id = $class_id;
            $model->teacher_id = $teacher_id;
            $model->subject_id = $subject;
            $model->school_id = $school_id;
            $model->status = 1;
            if ($model->save())
                $count = $count + 1;

            $this->AddClassSubject($subject, $class_id, $school_id);
        }
        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, $count > 0 ? $count . ' new subject assigned to teacher' : 'No new subject assigned');

    }

    //Assign teacher to class with subjects
    public function actionAssignSubjectsToTeacher()
    {
        $subjects = Yii::$app->request->post('subjects');
        $class_id = Yii::$app->request->post('class_id');
        $teacher_id = Yii::$app->request->post('teacher_id');
        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id', 'subjects', 'teacher_id'));
        $model->addRule(['class_id', 'school_id', 'subjects', 'teacher_id'], 'required');
        $model->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id', 'school_id' => 'school_id']]);
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => SchoolTeachers::className(), 'targetAttribute' => ['teacher_id', 'school_id']]);
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!is_array($subjects)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'subjects must be an array');
        }

        if (count($subjects) > SchoolSubject::find()->where(['subject_id' => $subjects, 'school_id' => $school_id])->count()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'One or more subject is not in your subject list.');
        }


        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!TeacherClass::find()->where(['teacher_id' => $teacher_id, 'school_id' => $school_id, 'class_id' => $class_id])->exists()) {
                $teacherClass = new TeacherClass();
                $teacherClass->teacher_id = $teacher_id;
                $teacherClass->class_id = $class_id;
                $teacherClass->school_id = $school_id;
                $teacherClass->status = 1;
                if (!$teacherClass->save())
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not add teacher to class');
            }

            foreach ($subjects as $subject) {
                if (TeacherClassSubjects::find()->where(['class_id' => $class_id, 'subject_id' => $subject, 'teacher_id' => $teacher_id])->exists())
                    continue;

                $model = new TeacherClassSubjects();
                $model->class_id = $class_id;
                $model->teacher_id = $teacher_id;
                $model->subject_id = $subject;
                $model->school_id = $school_id;
                $model->status = 1;
                if (!$model->save())
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not add one or more subjects');

                $this->AddClassSubject($subject, $class_id, $school_id);
            }


            $dbtransaction->commit();
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Successful');

    }

    public function AddClassSubject($subject, $class, $school)
    {
        if (ClassSubjects::find()->where(['subject_id' => $subject, 'class_id' => $class, 'school_id' => $school])->exists())
            return true;

        $model = new ClassSubjects();
        $model->class_id = $class;
        $model->school_id = $school;
        $model->subject_id = $subject;
        $model->status = 1;
        return $model->save();

    }
}