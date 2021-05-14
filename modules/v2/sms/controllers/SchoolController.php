<?php

namespace app\modules\v2\sms\controllers;

use app\modules\v2\components\SmsAuthentication;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolSubject;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\school\models\PreferencesForm;
use Yii;
use yii\data\ActiveDataProvider;
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


    public function actionStudents($student = null, $class = null, $license = null)
    {

        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        $classes = StudentSchool::find()
            ->where(['school_id' => $school->id]);

        if (!$classes->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No student available!');
        }

        $models = StudentSchool::find()
            ->alias('ss')
            ->select([
                'u.id',
                'u.firstname student_firstname',
                'u.lastname student_lastname',
                'u.image student_image',
                'u.lastname student_lastname',
                'pu.id parent_id',
                'pu.firstname parent_firstname',
                'pu.lastname parent_lastname',
                'pu.phone parent_phone',
                'pu.email parent_email',
                'p.role relationship',
                'pu.image parent_image',
                'ss.subscription_status',
                'cl.class_name',
            ])
            ->innerJoin('user u', 'u.id = ss.student_id')
            ->leftJoin('parents p', 'p.student_id = ss.student_id AND p.status = 1')
            ->leftJoin('user pu', 'pu.id = p.parent_id AND p.status = 1')
            ->leftJoin('classes cl', 'cl.id = ss.class_id')
            ->where(['ss.school_id' => $school->id, 'ss.status' => 1, 'ss.is_active_class' => 1]);

        if (!empty($class)) {
            $models = $models->andWhere(['cl.id' => $class]);
        }

        if (!empty($student)) {
            $models = $models->andFilterWhere(['OR', ['like', 'u.lastname', '%' . $student . '%', false],
                ['like', 'u.firstname', '%' . $student . '%', false],
                ['like', 'u.code', '%' . $student . '%', false]]);
        }

        if (!empty($license) && $license != 'all') {
            if ($license == 'disable')
                $license = null;
            $models = $models->andWhere(['ss.subscription_status' => $license]);
        }

        $models = $models->groupBy('u.id')->asArray();

        $dataProvider = new ActiveDataProvider([
            'query' => $models,
            'pagination' => [
                'pageSize' => 20,
                'validatePage' => false,
            ],
        ]);


//        $return = [
//            'students' => $dataProvider->getModels(),
//            'license' => Utility::SchoolStudentSubscriptionDetails($school)
//        ];

        return (new ApiResponse)->success($dataProvider->getModels(), ApiResponse::SUCCESSFUL, $models->count(), $dataProvider);

    }


    public function actionAddSubject()
    {
        $form = new PreferencesForm(['scenario' => 'add-subject']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $school = Schools::findOne(['id' => SmsAuthentication::getSchool()]);
        if (!$model = $form->addSubject($school)) {
            return (new ApiResponse)->error($form->errors, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subject not created');
        }

        return (new ApiResponse)->success($model);
    }
}