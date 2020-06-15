<?php

namespace app\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\models\{QuizSummary,QuizSummaryDetails,HomeworkQuestions};
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

/**
 * Adaptivity controller
 */
class AdaptivityController extends ActiveController
{
    public $modelClass = 'api\models\User';

    private $request; 

    public function beforeAction($action)
    {
        $this->request = \yii::$app->request->post();
        return parent::beforeAction($action);
    }
    
    /**
     * {@inheritdoc}
     */

    public function behaviors()
    {
        return [
            [
              'class' => \yii\ filters\ ContentNegotiator::className(),
              //'only' => ['index', 'view'],
              'formats' => [
                'application/json' => \yii\ web\ Response::FORMAT_JSON,
              ],
            ],
            
            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['logout'],
                'only' => [''],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];

        return [
            [
              'class' => \yii\ filters\ ContentNegotiator::className(),
              'formats' => [
                'application/json' => \yii\ web\ Response::FORMAT_JSON,
              ],
            ],
            
        ];
    }

    public function actionHomework():array{

        if($this->request['adaptivity_type'] == 'homework'){
            //check questions summary table to view performance of student
            //$checkStudentHomeworkActivity = QuizSummaryDetails::findAll(['student_id' => $this->request['student_id']]);
            $checkStudentTakenAnyHomework  = QuizSummaryDetails::findOne(['student_id' => $this->request['student_id']]);

            //if empty that means the the student has not taken homework
            //as a result show the student the first set of easy questions
            if(empty($checkStudentTakenAnyHomework)){

                $selectEasyQuestions = QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['homework_questions.difficulty' => 1])
                ->limit(10)
                ->all();
            }

            elseif(!empty($checkStudentTakenAnyHomework)){

                //i need to get the next topic
                $getLastHomework = QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                ->where(['homework_questions.difficulty' => 1])
                ->one();
                $getLastTopicId = $getLastHomework->topic_id;
                // var_dump($getLastHomework); exit;

                //check percentage score for current topic then based on this


                //first find out students performance in the last topic, then using 
                //gradely component check if student is qualified to move to the next topic
                //if student qualifies to move to the next topic proceed else keep showing questions
                //from current topic


                $checkStudentHomeworkPerformanceEasy = count(QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                //->where(['quiz_summary_details.selected' => 'quiz_summary_details.answer'])
                //->where('quiz_summary_details.selected != :quiz_summary_details.answer',['quiz_summary_details.selected' => 'quiz_summary_details.answer'])
                ->where(['homework_questions.difficulty' => 1])
                ->where(['quiz_summary_details.topic_id' => $getLastHomework->topic_id])
                ->andWhere(['!=', 'quiz_summary_details.selected', 'quiz_summary_details.answer'])
                ->limit(30)
                ->all());

                //var_dump($checkStudentHomeworkPerformanceEasy); exit;

                $totalFailedEasy = $checkStudentHomeworkPerformanceEasy;

                //var_dump($totalFailedEasy); exit;

                $checkStudentHomeworkPerformanceMedium = count(QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                ->where(['homework_questions.difficulty' => 2])
                ->where(['quiz_summary_details.topic_id' => $getLastHomework->topic_id])
                ->andWhere(['!=', 'quiz_summary_details.selected', 'quiz_summary_details.answer'])
                ->limit(10)
                ->all());

                $totalFailedMedium = $checkStudentHomeworkPerformanceMedium;

                $checkStudentHomeworkPerformanceHard = count(QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                ->where(['homework_questions.difficulty' => 3])
                ->where(['quiz_summary_details.topic_id' => $getLastHomework->topic_id])
                ->andWhere(['!=', 'quiz_summary_details.selected', 'quiz_summary_details.answer'])
                ->limit(10)
                ->all());

                $totalFailedHard  = $checkStudentHomeworkPerformanceHard;

                var_dump($totalFailedHard); exit;

                //next set of questions to send

                $numberQuestionsAdded = Yii::$app->GradelyComponent->getPercentageForNextQuestion($totalFailedEasy, $totalFailedMedium, $totalFailedHard);
                //if result returned is a valid array in order words if it has any valid result
                if(is_array($numberQuestionsAdded)){

                    foreach($numberQuestionsAdded as $key => $value){
                        $splitDifficulty = $value;
                    }

                    //next set of hard questions
                    $hardLimit = 10 + $splitDifficulty['hard'];
                    $getNewSetHardHomework = QuizSummaryDetails::find()
                    ->select('quiz_summary_details.*')
                    ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                    ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                    ->where(['homework_questions.difficulty' => 1])
                    ->limit($hardLimit)
                    ->all();

                    //next set of medium questions
                    $mediumimit = 10 + $splitDifficulty['medium'];
                    $getNewSetMediumHomework = QuizSummaryDetails::find()
                    ->select('quiz_summary_details.*')
                    ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                    ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                    ->where(['homework_questions.difficulty' => 2])
                    ->limit($mediumimit)
                    ->all();

                    //next set of easy questions
                    $easyLimit = 10 + $splitDifficulty['easy'];
                    $getNewSetEasyHomework = QuizSummaryDetails::find()
                    ->select('quiz_summary_details.*')
                    ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                    ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                    ->where(['homework_questions.difficulty' => 3])
                    ->limit($easyLimit)
                    ->all();

                    return [

                        'hardQuestions' => $getNewSetHardHomework,
                        'mediumQuestions' => $getNewSetMediumHomework,
                        'easyQuestions' => $getNewSetEasyHomework
                    ];
                }

                return[

                    'code' => '200',
                    'message' => 'No result returned from component'

                ];
            }

            return[
                'code' => '500',
                'message' => 'something went wrong'
            ];
        }
    }
}