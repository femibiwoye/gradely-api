<?php

namespace app\modules\v2\sms\controllers;

use app\modules\v2\components\SmsAuthentication;
use app\modules\v2\components\{Utility};
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TeacherClassSubjects;
use Yii;
use yii\db\Expression;
use yii\rest\ActiveController;


/**
 * Schools/Parent controller
 */
class ReportController extends ActiveController
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

    public function actionGetClassReport()
    {
        $class_id = Yii::$app->request->get('class_id');
        $subject_id = Yii::$app->request->get('subject_id');
        $term = Yii::$app->request->get('term');


        $form = new \yii\base\DynamicModel(compact('class_id', 'subject_id', 'term'));
        $form->addRule(['class_id', 'subject_id', 'term'], 'required');

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if (!(Classes::find()->where(['school_id' => SmsAuthentication::getSchool(), 'id' => $class_id])->exists())) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not have access to this class');
        }

        $students = StudentSchool::find()
            ->select([
                'student_id',
//                new Expression("CONCAT(user.firstname,' ',user.lastname) student_name"),
//                'image',
            ])
            ->innerJoin('user', 'user.id = student_school.student_id')
            ->where(['class_id' => $class_id, 'student_school.status' => 1, 'is_active_class' => 1, 'current_class' => 1])
            ->asArray()
            ->all();
        $models = [];
        $examModel = [];

        $temp = QuizSummary::find()
            ->alias('qs')
            ->select([
                //'qs.class_id',
                'qs.subject_id',
                'qs.id',
                'qs.homework_id',
                'qs.student_id',
                'qs.type',
                'qs.term',
                'subjects.name',
                'qs.total_questions',
                'qs.teacher_id',
                new Expression("CONCAT(user.firstname,' ',user.lastname) as teacher_name"),
                new Expression('round((SUM(qs.correct)/SUM(qs.total_questions))*100) as score'),
                'qs.created_at',

            ])
            ->leftJoin('homeworks h', "qs.homework_id = h.id")
            ->leftJoin('user', "user.id = h.teacher_id")
            ->leftJoin('subjects', "subjects.id = qs.subject_id")
            ->where(['qs.class_id' => $class_id, 'qs.subject_id' => $subject_id, 'qs.term' => $term])
            ->asArray()
            ->groupBy('qs.id')
            ->limit(6)
            ->all();
        $item = [];
        foreach ($temp as $index => $item) {

            $models[] = ['index' => $index + 1, 'data' => $item];
        }


        foreach ($students as $key => $student) {
            $canew = [];
            foreach ($models as $kkkey => $each) {
                if ($each['data']['student_id'] == $student['student_id']) {
                    //$canew[] = ["report_index"=>$each['index'],"report_score"=>(int)$each['data']['average_score'],'type'=>'ca'];
                }
            }

            $students[$key] = array_merge($student, $item
            // , ['scores' => $canew,]
            );

        }
        return (new ApiResponse)->success($students, ApiResponse::SUCCESSFUL);

    }

}