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
            //as a result present 10 easy, 10 medium and ten hard questions to the student
            if(empty($checkStudentTakenAnyHomework)){

                $selectEasyQuestions = QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['homework_questions.difficulty' => 1])
                ->limit(10)
                ->all();

                $selectMediumQuestions = QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['homework_questions.difficulty' => 2])
                ->limit(10)
                ->all();

                $selectHardQuestions = QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['homework_questions.difficulty' => 3])
                ->limit(10)
                ->all();
            }

            elseif(!empty($checkStudentTakenAnyHomework)){
                
                $checkStudentHomeworkPerformanceEasy = count(QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                ->where(['homework_questions.difficulty' => 1])
                ->limit(10)
                ->all());
                $totalFailedEasy = $checkStudentHomeworkPerformanceEasy - 10;

                $checkStudentHomeworkPerformanceMedium = count(QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                ->where(['homework_questions.difficulty' => 2])
                ->limit(10)
                ->all());
                $totalFailedMedium = $checkStudentHomeworkPerformanceMedium - 10;

                $checkStudentHomeworkPerformanceHard = count(QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                ->where(['homework_questions.difficulty' => 3])
                ->limit(10)
                ->all());
                $totalFailedHard  = $checkStudentHomeworkPerformanceHard - 10;

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