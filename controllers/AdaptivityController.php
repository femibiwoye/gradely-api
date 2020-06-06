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

    public function actionHomework(){

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

                $selectMediumQuestions = QuizSummaryDetails::find()
                ->select('quiz_summary_details.*')
                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                ->where(['homework_questions.difficulty' => 3])
                ->limit(10)
                ->all();
            }

            elseif(!empty($checkStudentTakenAnyHomework)){

                $checkStudentHomeworkActivity = QuizSummaryDetails::find()
                                                ->select('quiz_summary_details.*')
                                                ->innerJoin('homework_questions', '`quiz_summary_details`.`question_id` = `homework_questions`.`question_id`')
                                                ->where(['quiz_summary_details.student_id' => $this->request['student_id']])
                                                ->limit(5)
                                                ->all();
            }

            //var_dump($checkStudentHomeworkActivity); exit;
            return Yii::$app->GradelyComponent->getHomeworkAdaptivityCalculation($checkStudentHomeworkActivity);
        }
    }
}