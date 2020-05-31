<?php

namespace app\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\helpers\Utility;
use app\models\{User,Homeworks,SchoolTeachers,TutorSession,QuizSummary,QuizSummaryDetails,Schools,Classes,GlobalClass,TeacherClass,Questions};
use yii\db\Expression;
/**
 * Schools controller
 */
class ClassesController extends ActiveController
{
    public $modelClass = 'api\models\User';
    
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
                'only' => ['generate-class'],
                'only' => ['create-class'],
                'only' => ['view-class'],
                'only' => ['delete-class'],
                'only' => ['list-parents'],
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

    public function actionListTeachers($id):array{

        $getTeachers = User::find()
                            ->select('user.*')
                            ->innerJoin('teacher_class', '`user`.`id` = `teacher_class`.`teacher_id`')
                            ->where(['teacher_class.class_id' => $id])
                            ->all();
        if(!empty($getTeachers)){

            unset($getTeachers[0]->username);
            unset($getTeachers[0]->code);
            unset($getTeachers[0]->password_hash);
            unset($getTeachers[0]->password_reset_token);
            unset($getTeachers[0]->auth_key);
            unset($getTeachers[0]->class);
            unset($getTeachers[0]->status);
            unset($getTeachers[0]->subscription_expiry);
            unset($getTeachers[0]->subscription_plan);
            unset($getTeachers[0]->created_at);
            unset($getTeachers[0]->updated_at);
            unset($getTeachers[0]->verification_token);
            unset($getTeachers[0]->oauth_provider);
            unset($getTeachers[0]->token);
            unset($getTeachers[0]->token_expires);
            unset($getTeachers[0]->oauth_uid);
            unset($getTeachers[0]->last_accessed);

            return [
                'code' => '200',
                'message' => 'teachers succesfully listed',
                'data' => $getTeachers
            ];
        }

        return [
            'code' =>'404',
            'message' => 'teachers not found'
        ];
    }

    public function actionDetailedTeacherProfile($id):array{

        $getDetailedTeacherProfile = User::find()
        ->select('user.*')
        ->innerJoin('teacher_class', '`teacher_class`.`teacher_id` = `user`.`id`')
        ->where(['teacher_class.teacher_id' => $id])
        ->one();

        $getClasses =   Count(
                            TeacherClass::find()->where(['teacher_id' => $id])->all()
                        );

        $liveSessions = Count(
                            TutorSession::find()
                            ->select('tutor_session.*')
                            ->innerJoin('teacher_class', '`teacher_class`.`class_id` = `tutor_session`.`class`')
                            ->where(['teacher_class.teacher_id' => $id])
                            ->where(['tutor_session.status' => '2'])
                            ->all()
                        );
        

        $homeWorks = Count(
                        Homeworks::find()
                        ->select('homeworks.*')
                        ->innerJoin('school_teachers', '`school_teachers`.`teacher_id` = `homeworks`.`teacher_id`')
                        ->where(['homeworks.teacher_id' => $id])
                        ->all()
                    );

        $classes = Classes::find()
                        ->select('classes.*')
                        ->innerJoin('teacher_class', '`teacher_class`.`class_id` = `classes`.`id`')
                        ->where(['teacher_class.teacher_id' => $id])
                        ->all();

        $getTeacherUserId = TeacherClass::findOne(['teacher_id' => $id]);

        $userObject ="";

        if(!empty($getTeacherUserId)){
            $userObject = User::findOne(['id' => $getTeacherUserId->teacher_id]);

            unset($userObject->auth_key);
            unset($userObject->password_hash);
            unset($userObject->password_reset_token);
            unset($userObject->token);
            unset($userObject->token_expires);
        }

        $topHomework = Count(Homeworks::find()
        ->select('homeworks.*')
        ->where(['teacher_id' => $id])
        ->limit(5)
        ->all());

        $topHomeworkPercentage = QuizSummary::find()
                                    ->select('quiz_summary.*')
                                    ->innerJoin('homeworks', '`quiz_summary`.`teacher_id` = `homeworks`.`teacher_id`')
                                    ->where(['homeworks.teacher_id' =>$id])
                                    ->where(['quiz_summary.type' =>'1'])
                                    ->where(['quiz_summary.submit' =>'1'])
                                    ->limit(5)
                                    ->all();

        $activitiesDueTodayHomeWork = Homeworks::find()->where(['teacher_id' => $id,'close_date' => date('Y-m-d H:i:s')])->all();

        $getTeacherUserId = User::findOne(['id' => $id]);
        $activitiesDueTodayClassSession = "";
        if(!empty($getTeacherUserId)){

            $activitiesDueTodayClassSession = TutorSession::find()->where(['requester_id' => $getTeacherUserId->id])->all();
        }
        return [
            'code' => '200',
            'message' => 'teachers succesfully listed',
            'data' =>   [
                            'name' => $getDetailedTeacherProfile->firstname.' '.$getDetailedTeacherProfile->lastname,
                            'number_classes' => $getClasses ?? 0,
                            'live_sessions' => $liveSessions ?? 0,
                            'homework' => $homeWorks ?? 0,
                            'about' => '',
                            'classes' =>$classes ?? 0,
                            'user' => $userObject ?? '',
                            'topHomework' => 
                                [
                                'total' => $topHomework ?? 0,
                                'percentage' => $topHomeworkPercentage[0]->total_questions / $topHomeworkPercentage[0]->correct *100
                                ],
                            'activitiesDueTodayHomeWork' => $activitiesDueTodayHomeWork ?? 0,
                            'activitiesDueTodayClassSession' => $activitiesDueTodayClassSession ?? 0
                        ]
        ];
    }

    public function actionHomeworkCreatedByTeacher($id):array{

        $homeworkCreatedByTeacher = Homeworks::find()->where(['teacher_id' => $id])->all();

        if(!empty($homeworkCreatedByTeacher)){
            return[
                'code' => '200',
                'message' => 'successful',
                'data' => $homeworkCreatedByTeacher
            ];
        }

        return[
            'code' => '404',
            'message' => 'could not find any homework for this teacher',
        ];
    }

    public function actionRemoveTeacherFromClass($id):array{

        $findTeacher = TeacherClass::findOne(['teacher_id' => $id]);

        if(!empty($findTeacher)){
            try{

                $findTeacher->delete();
                return[
                    'code' => '404',
                    'message' => 'Teacher Removed from class',
                ];
            }
            catch(Exception $exception){
                return[
                    'code' => '200',
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return[
            'code' => '404',
            'message' => 'could not find teacher',
        ];
    }

    public function actionHomeworkClassList($id):array{
        $getAllHomework = Homeworks::find()->where(['class_id' => $id])->all();

        if(!empty($getAllHomework)){

            return [

                'code' => '200',
                'message' => 'Listing successful',
                'data' => $getAllHomework
            ];
        }
        else{
                return [
    
                    'code' => '200',
                    'message' => 'Could not find any homework under this class'
                ];
        }
    }

    public function actionHomeworkPerformance($id):array{
        
        $getAllHomeworkPerformance = QuizSummaryDetails::find()
                            ->select('quiz_summary_details.*')
                            ->innerJoin('homeworks',  '`quiz_summary_details`.`id` = `homeworks`.`homework_id`')
                            ->where(['homeworks.class_id' =>$id])
                            ->limit(30)
                            ->all();

        if(!empty($getAllHomeworkPerformance)){

            return [

                'code' => '200',
                'message' => 'Listing successful',
                'data' => $getAllHomeworkPerformance
            ];
        }
        else{
                return [
    
                    'code' => '200',
                    'message' => 'Could not find any homework under this class'
                ];
        }
    }


    public function actionHomeworkReview($id):array{

        //calculate average
        $averageClassScores = QuizSummary::find()
                                 ->where(['class_id' => $id,'submit' => 1])
                                 //->average('correct')
                                 ->all();

        $i = 0; $allAverageScores = 0;
        foreach($averageClassScores as $averageClassScore){
            $allAverageScores += $averageClassScore->topic_id;
            $i++;
        }

        $resultAverageScore = $allAverageScores/$i;

        //calculate best performing topic
        $bestPerformingTopics = QuizSummary::find()
                                ->where(['class_id' => $id,'submit' => 1])
                                ->orderBy(['correct' => SORT_DESC])
                                ->limit(10)
                                //->average('correct')
                                ->all();


        $bestPerformingTopicsArray = [];
        foreach($bestPerformingTopics as $bestPerformingTopic){
                $bestPerformingTopicsArray[] = $bestPerformingTopic->topic_id;
        }

        // var_dump($bestPerformingTopicsArray);
        // exit;


        //calculate least performing topic
        $leastPerformingTopics = QuizSummary::find()
                                ->where(['class_id' => $id,'submit' => 1])
                                ->orderBy(['correct' => SORT_ASC])
                                ->limit(10)
                                //->average('correct')
                                ->all();


        $leastPerformingTopicsArray = [];
        foreach($leastPerformingTopics as $leastPerformingTopic){
                $leastPerformingTopicsArray[] = $leastPerformingTopic->topic_id;
        }

        //calculate completion rate
        $completionRate =   Count(
                                    QuizSummary::find()->where(['class_id' => $id,'submit' => 1])->all()
                            );

        
        $getHomeworks = QuizSummary::find()
                            ->select(' quiz_summary.*')
                            ->innerJoin('quiz_summary_details', '`quiz_summary_details`.`quiz_id` = `quiz_summary`.`id`')
                            ->where(['quiz_summary.class_id' => $id])
                            ->where(['quiz_summary.type' => 1])
                            ->all();

        $allHomeworkIds = [];                    
        foreach($getHomeworks as $getHomework){
            $allHomeworkIds[] = $getHomework->homework_id;
        }

        $getAllQuestions = [];
        foreach($allHomeworkIds as $allHomeworkId){
            $getAllQuestions[] = Questions::find()->where(['homework_id' => $allHomeworkId])->all();
        }

        $attemptedQuestions = $getAllQuestions;

        return [

            'code' => '200',
            'message' => 'Listing successful',
            'data' => 
            [
                'averageClassScore' => $resultAverageScore ?? 0,
                'completionRate' => $completionRate ?? 0,
                'attemptedQuestions' => $attemptedQuestions ?? 0,
                'bestPerformingTopic' => $bestPerformingTopicsArray ?? 0,
                'leastPerformingTopic' => $leastPerformingTopicsArray ?? 0
            ]
        ];
    }
}