<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\modules\v1\models\{QuizSummary,QuizSummaryDetails,HomeworkQuestions,StudentSchool,Questions};
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

            //check questions summary table to view performance of student
            //$checkStudentHomeworkActivity = QuizSummaryDetails::findAll(['student_id' => $this->request['student_id']]);
            $checkStudentTakenAnyHomework  = QuizSummaryDetails::findOne(['student_id' => $this->request['student_id']]);
            $getStudentCurrentDifficultyLevel = StudentSchool::findOne(['student_id' => $this->request['student_id']]);
            
            $currentDifficultyLevel = $getStudentCurrentDifficultyLevel->homework_difficulty_level;

            //if empty that means the the student has not taken homework
            //as a result present 10 easy, 10 medium and 10 hard questions to the student
            if(empty($checkStudentTakenAnyHomework)){

                //the first set of questions to present to the student should be medium questions, its when student fails medium question 
                //given he can then go back to easy else if he does weel he proceeds to hard

                $selectMediumQuestions = QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                //if student would ever had taken a practice test he would definitely had started with easy
                //questions hence the reason i am checking for easy difficulty
                ->where(['homework_questions.difficulty' => 2])
                ->where(['homeworks.class_id' => $this->request['class_id']])
                ->where(['homeworks.school_id' => $this->request['school_id']])
                ->where(['homeworks.type' => 1])
                ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                ->all();

            }

            elseif(!empty($checkStudentTakenAnyHomework)){

                //var_dump($checkStudentTakenAnyHomework); exit;

                $currentDifficultyLevel = 2;
                $getLastHomework = QuizSummaryDetails::find()

                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                ->one();

                //var_dump($getLastHomework); exit;

                if(!empty($getLastHomework)){
                    
                    $getLastTopicId = $getLastHomework->topic_id;

                    //check percentage score for current topic then based on this

                    //first find out students performance in the last difficulty, then using 
                    //gradely component check if student is qualified to move to the next difficulty / topic
                    //if student qualifies to move to the next difficulty / topic proceed else 
                    //keep showing questions from current topic
                    
                    $checkStudentHomeworkPerformance = count(QuizSummaryDetails::find()
                    ->select('quiz_summary_details.*')
                    ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                    ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                    ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                    ->where(['homework_questions.difficulty' => $currentDifficultyLevel])
                    ->where(['homeworks.type' => 1])
                    ->andWhere(['!=', 'quiz_summary_details.selected', 'quiz_summary_details.answer'])
                    ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                    ->all());
                    

                    //var_dump($checkStudentHomeworkPerformance); exit;
                    $totalFailed = Yii::$app->GradelyComponent->numberQuestionsPerTime - $checkStudentHomeworkPerformance;

                    $getDetailsForNextQuestion = Yii::$app->GradelyComponent->getParametersForNextSetOfQuestion($totalFailed,$currentDifficultyLevel);
                    
                    //var_dump($getDetailsForNextQuestion); exit;
                    //if result returned is a valid array in order words if it has any valid result
                    if(is_array($getDetailsForNextQuestion)){

                        //if proceed equals true that means the student can now proceed 
                        //if false the student gets to see
                        //questions from the current difficulty
                        if($getDetailsForNextQuestion['proceed'] == true){
                            //next set of questions
                            $getNewSetOfQuestions = QuizSummaryDetails::find()
                            ->select('quiz_summary_details.*')
                            ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                            ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                            ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                            ->where(['homework_questions.difficulty' => $getDetailsForNextQuestion['nextDificulty']])
                            ->where(['quiz_summary_details.topic_id' => $getLastTopicId])
                            ->where(['homeworks.type' => 1])
                            ->where(['homeworks.class_id' => $this->request['class_id']])
                            ->where(['homeworks.school_id' => $this->request['school_id']])
                            ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                            ->all();

                            return [
                                'questions' => $getNewSetOfQuestions
                            ];
                        }

                        elseif($getDetailsForNextQuestion['proceed'] == false){
                            //next set of questions
                            $getNewSetOfQuestions = QuizSummaryDetails::find()
                            ->select('quiz_summary_details.*')
                            ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                            ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                            ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                            ->where(['homework_questions.difficulty' => $getDetailsForNextQuestion['nextDificulty']])
                            ->where(['quiz_summary_details.topic_id' => $getLastTopicId])
                            ->where(['homeworks.type' => 1])
                            ->where(['homeworks.class_id' => $this->request['class_id']])
                            ->where(['homeworks.school_id' => $this->request['school_id']])
                            ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                            ->all();

                            return [
                                'questions' => $getNewSetOfQuestions
                            ];
                        }

                        if($getDetailsForNextQuestion['proceed'] == false){
                            //next set of questions
                            $getNewSetOfQuestions = QuizSummaryDetails::find()
                            ->select('quiz_summary_details.*')
                            ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                            ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                            ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                            ->where(['homework_questions.difficulty' => $getDetailsForNextQuestion['nextDificulty']])
                            ->where(['quiz_summary_details.topic_id' => $getLastTopicId])
                            ->where(['homeworks.type' => 1])
                            ->where(['homeworks.class_id' => $this->request['class_id']])
                            ->where(['homeworks.school_id' => $this->request['school_id']])
                            ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                            ->all();

                            return [
                                'questions' => $getNewSetOfQuestions
                            ];
                        }
                    }

                    return[

                        'code' => '200',
                        'message' => 'No valid response returned from component'

                    ];
                }
            }

            return[
                'code' => '500',
                'message' => 'something went wrong'
            ];
    }

    public function actionPracticeSet():array{

        //based on students homework performance generate practice set, 
        //its the same appraoch with homework the difference is just that 
        //practice set is comming from catch table
            $checkStudentTakenAnyHomework  = QuizSummaryDetails::find()
            ->select('quiz_summary_details.*')
            ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
            ->where(['homeworks.type' => 2])
            ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
            ->one();

            // var_dump($checkStudentTakenAnyHomework); exit;

            //$currentDifficultyLevel = $getStudentCurrentDifficultyLevel->homework_difficulty_level;
            //if empty that means the the student has not taken homework
            //as a result present 10 easy, 10 medium and 10 hard questions to the student

            if(empty($checkStudentTakenAnyHomework)){

                $selectMediumQuestions = QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                //if student would ever had taken a practice test he would definitely had started with easy
                //questions hence the reason i am checking for easy difficulty
                ->where(['homework_questions.difficulty' => 2])
                ->where(['homeworks.class_id' => $this->request['class_id']])
                ->where(['homeworks.school_id' => $this->request['school_id']])
                ->where(['homeworks.type' => 1])
                ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                ->all();
            }

            elseif(!empty($checkStudentTakenAnyHomework)){

                $currentDifficultyLevel = 1;
                $getLastHomework = QuizSummaryDetails::find()

                ->select('quiz_summary_details.*')
                ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                ->where(['homework_questions.difficulty' => $currentDifficultyLevel])
                ->where(['homeworks.type' => 2])
                ->one();

                if(!empty($getLastHomework)){
                    
                    $getLastTopicId = $getLastHomework->topic_id;

                    //var_dump($getLastTopicId); exit;

                    //check percentage score for current topic then based on this

                    //first find out students performance in the last difficulty, then using 
                    //gradely component check if student is qualified to move to the next difficulty / topic
                    //if student qualifies to move to the next difficulty / topic proceed else 
                    //keep showing questions from current topic
                    
                    $checkStudentHomeworkPerformance = count(QuizSummaryDetails::find()
                    ->select('quiz_summary_details.*')
                    ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                    ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                    ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                    ->where(['homework_questions.difficulty' => $currentDifficultyLevel])
                    ->where(['homeworks.type' => 2])
                    ->andWhere(['!=', 'quiz_summary_details.selected', 'quiz_summary_details.answer'])
                    ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                    ->all());

                    //var_dump($checkStudentHomeworkPerformance); exit;
                    $totalFailed = Yii::$app->GradelyComponent->numberQuestionsPerTime - $checkStudentHomeworkPerformance;

                    $getDetailsForNextQuestion = Yii::$app->GradelyComponent->getParametersForNextSetOfQuestion($totalFailed,$currentDifficultyLevel);

                    //if result returned is a valid array in order words if it has any valid result
                    if(is_array($getDetailsForNextQuestion)){

                        //if proceed equals true that means the student can now proceed 
                        //to the next set of questions that means we will have $getNextquestionArray['nextDificulty']+1,
                        //if false the student gets to see
                        //questions from the current difficulty
                        if($getDetailsForNextQuestion['proceed'] == true){
                            //next set of questions
                            $getNewSetOfQuestions = QuizSummaryDetails::find()
                            ->select('quiz_summary_details.*')
                            ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                            ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                            ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                            ->where(['homework_questions.difficulty' => $getDetailsForNextQuestion['nextDificulty']])
                            ->where(['quiz_summary_details.topic_id' => $getLastTopicId])
                            ->where(['homeworks.type' => 2])
                            ->where(['homeworks.class_id' => $this->request['class_id']])
                            ->where(['homeworks.school_id' => $this->request['school_id']])
                            ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                            ->all();

                            return [
                                'questions' => $getNewSetOfQuestions
                            ];
                        }

                        elseif($getDetailsForNextQuestion['proceed'] == false){
                            //next set of questions
                            $getNewSetOfQuestions = QuizSummaryDetails::find()
                            ->select('quiz_summary_details.*')
                            ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                            ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                            ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                            ->where(['homework_questions.difficulty' => $getDetailsForNextQuestion['nextDificulty']])
                            ->where(['quiz_summary_details.topic_id' => $getLastTopicId])
                            ->where(['homeworks.type' => 2])
                            ->where(['homeworks.class_id' => $this->request['class_id']])
                            ->where(['homeworks.school_id' => $this->request['school_id']])
                            ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                            ->all();

                            return [
                                'questions' => $getNewSetOfQuestions
                            ];
                        }

                        if($getDetailsForNextQuestion['proceed'] == false){
                            //next set of questions
                            $getNewSetOfQuestions = QuizSummaryDetails::find()
                            ->select('quiz_summary_details.*')
                            ->innerJoin('homeworks', '`quiz_summary_details`.`topic_id` = `homeworks`.`topic_id`')
                            ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                            ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                            ->where(['homework_questions.difficulty' => $getDetailsForNextQuestion['nextDificulty']])
                            ->where(['quiz_summary_details.topic_id' => $getLastTopicId])
                            ->where(['homeworks.type' => 2])
                            ->where(['homeworks.class_id' => $this->request['class_id']])
                            ->where(['homeworks.school_id' => $this->request['school_id']])
                            ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
                            ->all();

                            return [
                                'questions' => $getNewSetOfQuestions
                            ];
                        }
                    }

                    return[

                        'code' => '200',
                        'message' => 'No valid response returned from component'

                    ];
                }
            }

            return[
                'code' => '500',
                'message' => 'something went wrong'
            ];
    }

    public function actionDiagonistic():array{

        $getDiagonisticQuestions = Questions::find()
        ->select('questions.*')
        //->where(['questions.type' => 2])
        ->where(['questions.class_id' => $this->request['class_id']])
        ->where(['questions.school_id' => $this->request['school_id']])
        ->limit(Yii::$app->GradelyComponent->numberQuestionsPerTime)
        ->all();

        return [
            'questions' => $getDiagonisticQuestions
        ];
    }
}