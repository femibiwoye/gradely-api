<?php

namespace app\modules\v2\student\controllers;

use app\modules\v1\models\StudentSchool;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\HomeworkQuestions;
use app\modules\v2\models\Homeworks;
use app\modules\v2\models\Questions;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\TeacherClass;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;

class PracticeController extends Controller
{

    public $modelClass = 'app\modules\v2\models\PracticeTopics';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        //Add CORS Filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
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

    public function actionHomeworkInstruction($homework_id){

        $studentClass = StudentSchool::findOne(['student_id' => \Yii::$app->user->id]);

        $homework = Homeworks::find()
            ->where(['id'=>$homework_id,'class_id'=>$studentClass->class_id])
            ->one();

        if(!$homework){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No homework found!');
        }

        return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homework retrieved');

    }

    public function actionStartHomework(){

        $homework_id = \Yii::$app->request->post('homework_id');

        $homework = Homeworks::find()
                    ->innerJoin('quiz_summary', 'quiz_summary.homework_id = homeworks.id')
                    ->andWhere(['homeworks.id' => $homework_id, 'homeworks.student_id' => \Yii::$app->user->id]);

        if($homework->andWhere(['quiz_summary.submit' => SharedConstant::VALUE_ZERO])->exists()){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student has not submitted homework');
        }

        if($homework->andWhere(['quiz_summary.submit' => SharedConstant::VALUE_ONE])->exists()){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student has submitted homework');
        }

        $model = new \yii\base\DynamicModel(compact('class_id', 'type', 'subject_id', 'topic_id', 'question_count', 'duration', 'teacher_id', 'homework_id'));
        $model->addRule(['homework_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id', 'student_id' => 'student_id']]);
        $model->addRule(['class_id'], 'exist', ['targetClass' => \app\modules\v2\models\StudentSchool::className(), 'targetAttribute' => ['class_id' => 'class_id', 'student_id' => 'student_id']]);
        $model->addRule(['subject_id'], 'exist', ['targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']]);
        //$model->addRule(['topic_id'], 'exist', ['targetClass' => SubjectTopics::className(), 'targetAttribute' => ['topic_id' => 'id', 'subject_id' => 'subject_id']]);
        $model->addRule(['type'], 'in', ['range' => ['homework']]);
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
        }

        $model = new QuizSummary();
        $model->attributes = \Yii::$app->request->post();
        $model->teacher_id = $homework->teacher_id;
        $model->student_id = $homework->student_id;
        $model->class_id = $homework->student_id;
        $model->subject_id = $homework->subject_id;
        $model->type = 'homework';

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
        }

        $homework_questions = HomeworkQuestions::find()->alias('hq')
                              ->innerJoin('homeworks', 'homeworks.id = hq.homework_id')
                              ->innerJoin('questions', 'questions.id = hq.question_id')
                              ->andWhere(['hq.homework_id' => $homework_id])
                              ->all();
        $model->question_count = Count($homework_questions);

        if(!$model->save()){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No homework assigned for you');
        }

        return (new ApiResponse)->success($homework_questions, ApiResponse::SUCCESSFUL, 'Homework questions retrieved');


    }
}
