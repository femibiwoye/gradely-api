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
use app\modules\v2\models\QuizSummaryDetails;
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

        $studentClass = StudentSchool::findOne(['student_id' => \Yii::$app->user->id]);
        $class_id = $studentClass->class_id;

        $model = new \yii\base\DynamicModel(compact('class_id', 'homework_id'));
        $model->addRule(['homework_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id', 'class_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not validated');
        }

        $homework = Homeworks::find()
            ->where(['homeworks.id' => $homework_id,'publish_status'=>1])->one();

        if(!$quizSummary = QuizSummary::find()->where(['homework_id'=>$homework_id, 'student_id'=>\Yii::$app->user->user->id])->one())
        {
            $model = new QuizSummary();
            $model->attributes = \Yii::$app->request->post();
            $model->teacher_id = $homework->teacher_id;
            $model->student_id = \Yii::$app->user->id;
            $model->class_id = $studentClass->class_id;
            $model->subject_id = $homework->subject_id;
            $model->type = 'homework';

            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not validated');
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

    //return message that homework is started
    }elseif($quizSummary->submit == 0){
        return (new ApiResponse)->error($homework, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework Started');

    }else{
        //Quiz is either invalid or already submitted.
    return (new ApiResponse)->error($homework, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework Submitted');
    }}

    public function actionProcessHomework(){

        $questionIds = \Yii::$app->request->post('attempts');
        $quiz_id = \Yii::$app->request->post('quiz_id');

        $student_id = \Yii::$app->user->id;

        $failedCount = 0;
        $correctCount = 0;
        $submit = 0;

        $model = new \yii\base\DynamicModel(compact('questionIdd', 'quiz_id','student_id'));
        $model->addRule(['questionsIds','quiz_id'],'required')
            ->addRule(['quiz_id'], 'exist', ['targetClass' => QuizSummary::className(), 'targetAttribute' => ['quiz_id' => 'id', 'student_id']]);

        if(!is_array($questionIds)){
            //return error that questions is invalid
            return (new ApiResponse)->error(null, ApiResponse::SUCCESSFUL, 'Question is Invalid');

        }

        //use transaction before saving;
        $dbtransaction = \Yii::$app->db->beginTransaction();
        try {

        foreach($questionIds as $question){
            $qsd = new QuizSummary();
            $qsd->question_id = $question->question;
            $qsd->selected = $question->selected;
            $questionModel = Questions::findOne(['id'=>$question->question]);
            $qsd->answer = $questionModel->answer;
            $qsd->topic_id = $questionModel->topic_id;
            $qsd->student_id = \Yii::$app->user->id;
            $qsd->homework_id = QuizSummary::findOne(['id'=>$quiz_id])->homework_id;

            if($question->selected != $questionModel->answer)
                $failedCount =+ 1;

            if($question->selected == $questionModel->answer)
                $correctCount =+ 1;

            if(!$qsd->save())
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question is Invalid');
        }

            $dbtransaction->commit();
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }


        $quiz_summary = QuizSummary::findOne(['quiz_id' => $quiz_id, 'student_id' => \Yii::$app->user->id]);

        if(!$quiz_summary)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework Invalid');


        $total_question = HomeworkQuestions::find()->where(['homework_id' => $quiz_summary->homework_id])->count();

        if(!$total_question)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No question!');

        $quiz_summary->failed = $failedCount;
        $quiz_summary->correct = $correctCount;
        $quiz_summary->total_questions = $total_question;
        $quiz_summary->skipped = $total_question - ($correctCount + $failedCount);
        $quiz_summary->submit = SharedConstant::VALUE_ONE;

        if($quiz_summary->save())
            return (new ApiResponse)->success($quiz_summary, ApiResponse::SUCCESSFUL, 'Homework Retrieved');
    }
}
