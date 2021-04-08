<?php

namespace app\modules\v2\sms\controllers;

use app\modules\v2\components\SmsAuthentication;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolSubject;
use Yii;
use yii\db\Expression;
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

    public function actionSchool()
    {
        $model = \app\modules\v2\sms\models\Schools::find()->select(['school_id', 'status', 'school_key', 'school_secret', 'created_at', 'approved'])->where(['school_id' => SmsAuthentication::getSchool()])->one();
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL);
    }

    public function actionSubjects()
    {
        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        $mySubjects = SchoolSubject::find()
            ->alias('s')
            ->select([
//                's.id',
//                's.school_id',
                'subjects.id as subject_id',
                'subjects.slug',
                'subjects.name',
                'subjects.description',
                //'count(d.class_id) class_subject_count',
                //'count(c.id) teacher_class_count'
//                new Expression('CASE WHEN h.subject_id IS NULL THEN 1 ELSE 0 END as can_delete'),
                new Expression("(SELECT COUNT(*) FROM class_subjects d WHERE d.subject_id = s.subject_id AND school_id = '$school->id' AND d.status = 1) AS class_subject_count")
            ])
            ->where(['s.school_id' => $school->id, 's.status' => 1])
            //->leftJoin('teacher_class_subjects c', "c.subject_id = s.subject_id AND c.school_id = '$school->id'")
            //->leftJoin('class_subjects d', "d.subject_id = s.subject_id AND d.school_id = '$school->id' AND d.status = 1")
            ->innerJoin('subjects', "subjects.id = s.subject_id")
            ->leftJoin('homeworks h', "h.subject_id = s.subject_id AND h.school_id = s.school_id")
            ->groupBy(['s.subject_id'])
            ->asArray();

        if (!$mySubjects->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No subject available!');
        }
        return (new ApiResponse)->success($mySubjects->all(), ApiResponse::SUCCESSFUL, $mySubjects->count() . ' subjects found');
    }

    public function actionClasses()
    {
        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);

        $getAllClasses = Classes::find()
            ->select([
                'classes.id',
                'classes.slug',
                'class_code',
                'class_name',
                'abbreviation',
                'global_class_id',
                //'classes.school_id',
                //'schools.name school_name',
                //new Expression('CASE WHEN h.class_id IS NULL THEN 1 ELSE 0 END as can_delete')
            ])
            ->leftJoin('schools', 'schools.id = classes.school_id')
            ->leftJoin('homeworks h', "h.class_id = classes.id AND h.school_id = classes.school_id")
            ->where(['classes.school_id' => $school->id])
            ->asArray();


        if (!$getAllClasses->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No classes available!');
        }
        return (new ApiResponse)->success($getAllClasses->all(), ApiResponse::SUCCESSFUL, $getAllClasses->count() . ' classes found');
    }

    public function actionClassSubjects($classes)
    {
        $classes = json_decode($classes);
        if (!is_array($classes)) {
            return (new ApiResponse)->success(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class(es) must be an array.');
        }

        $category = [];
        foreach ($classes as $class) {
            if ($class >= 7 && $class <= 12)
                $category[] = 'secondary';
            elseif ($class >= 1 && $class <= 6 || $class > 12)
                $category[] = 'primary';
            else
                $category[] = null;
        }
        $category[] = 'all';
        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        $mySubjects = SchoolSubject::find()
            ->alias('s')
            ->select([

                'subjects.id',
                'subjects.slug',
                'subjects.name',
                'subjects.description',
            ])
            ->innerJoin('subjects', "subjects.id = s.subject_id")
            ->where(['s.school_id' => $school->id, 's.status' => 1, 'subjects.category' => $category])
            ->groupBy(['s.subject_id'])
            ->asArray()->all();

        if ($mySubjects) {
            return (new ApiResponse)->success($mySubjects, ApiResponse::SUCCESSFUL);
        }

        return (new ApiResponse)->error(null, ApiResponse::NOT_FOUND);
    }


}