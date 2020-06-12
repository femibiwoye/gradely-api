<?php

namespace app\modules\v1\school\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v1\helpers\Utility;
use app\modules\v1\models\{User,Homeworks,SchoolTeachers,TutorSession,QuizSummary,QuizSummaryDetails,Schools,Classes,GlobalClass,TeacherClass,Questions};
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

    private $request;

    public function beforeAction($action)
    {
        $this->request = \yii::$app->request->post();
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [

            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'list-teachers' => ['get'],
                    'detailed-teacher-profile' => ['get'],
                    'homework-created-by-teacher' => ['get'],
                    'remove-teacher-from-class' => ['delete'],
                    'generate-class' => ['post'],
                    'list-class' => ['get'],
                    'create-class' => ['post'],
                    'view-class' => ['get'],
                    'update-class' => ['update'],
                    'delete-class' => ['delete'],
                    'list-parents' => ['get'],
                    'homework-performance' => ['get'],
                    'homework-review' => ['get'],
                    'remove-child-class' => ['delete'],
                    'change-student-class' => ['update'],
                    'get-class-details' => ['get'],
                    'list-students-class' => ['post'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => [
                            'list-teachers','detailed-teacher-profile','homework-created-by-teacher',
                            'remove-teacher-from-class','generate-class','list-class','create-class',
                            'view-class','update-class','delete-class','list-homework',
                            'homework-performance','homework-review','remove-child-class',
                            'change-student-class','get-class-details','list-students-class'
                        ]
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
                                'percentage' => Yii::$app->GradelyComponent->getTopHomeworkPercentage($topHomeworkPercentage[0]->total_questions, $topHomeworkPercentage[0]->correct),
                                //'percentage' => $topHomeworkPercentage[0]->total_questions / $topHomeworkPercentage[0]->correct *100
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

    public function actionGenerateClass(){

        //$this->getSchoolType('primary','grade 1-12');
        //$this->getSchoolType('secondary','grade 1-12');
        //$this->getSchoolType('primary','year 1-12');
        //$this->getSchoolType('secondary','year 1-12');
        //$this->getSchoolType('primary-secondary','year 1-12');
        $generateSchool = $this->getSchoolType($this->request['school-type'],$this->request['format']);
    
        if($generateSchool){
    
            Yii::info('[Class generated succesfully]');
            return[
                'code' => 200,
                'message' => "Successfully created"
            ];
        }
    }
    
    public function actionCreateClass(){

        $classes = new Classes();
        $classes->school_id = $this->request['school_id'];
        $classes->global_class_id = $this->request['global_class_id'];
        $classes->class_name = $this->request['class_name'];
        $classes->class_code = $this->request['class_code'];
        $classes->slug = \yii\helpers\Inflector::slug($this->request['class_name']);
        $classes->abbreviation = $this->abreviate($classes->slug);

        if ($classes->save()) {
            return[
                'code' => 200,
                'message' => "Successfully created"
            ];
        }

        $classes->validate();
        Yii::info('[Class generated succesfully] Error:'.$classes->validate().'');
        return $classes;
    }

    public function actionListClass(){


        //create a method that gets the users bearer token from the header
        // then using the bearer token, get the userid, then use the userid to get to get to get the schoolid
        //then use the schooid to list
        $getAllClasses = classes::find()->where(['school_id' => Utility::getSchoolId()])->all();
        

        if(!empty($getAllClasses)){

            Yii::info('[Class Listing succesful] school_id:'.Utility::getSchoolId().'');
            return[
                'code' => 200,
                'message' => "Succesfull",
                'data'=> $getAllClasses
            ];
        }

        Yii::info('[Couldnt find any class for this school] school_id:'.Utility::getSchoolId().'');
        return[
            'code' => 200,
            'message' => "Couldnt find any class for this school"
        ];
    }

    public function actionViewClass($id){

        $getClass = Classes::findOne(['school_id' => $id]);

        if(!empty($getClass)){

            Yii::info('[Class view successful] school_id:'.$id.'');
            return[
                'code' => 200,
                'message' => "Class view successful",
                'data' => $getClass
            ];
        }

        Yii::info('[Could not find any class with this id under this school] school_id:'.$id.'');
        return[
            'code' => 200,
            'message' => "Could not find any class with this id under this school"
        ];
    }

    public function actionUpdateClass($id){

        $getClass = Classes::find()->where(['id' => $id])->one();
        if(!empty($getClass)){
            
            $getClass->global_class_id = $this->request['global_class_id'];
            $getClass->class_name = $this->request['class_name'];
            $getClass->abbreviation = $this->request['class_code'];

            try{
                
                $getClass->save();
                Yii::info('[Class update successful] school_id:'.$id.'');
                return[
                    'code' => '200',
                    'message' => "Class update succesful"
                ];
            }
            catch (Exception $exception){
                Yii::info('[Class update successful] '.$exception->getMessage());
                return[
                    'code' => '500',
                    'message' => $exception->getMessage()
                ];
            }
        }

        Yii::info('[class does not exist] Class ID:'.$id);
        return[
            'code' => 200,
            'message' => 'class does not exist'
        ];
    }

    public function actionDeleteClass($id){

        $getClass = Classes::findOne(['id' => $id]);

        if(!empty($getClass)){

            try{
                $getClass->delete();
                Yii::info('[class delete succesful] Class ID:'.$id);
                return[
                    'code' => '200',
                    'message' => "Class delete succesful"
                ];
            }
            catch (Exception $exception){
                return[
                    'code' => '500',
                    'message' => $exception->getMessage()
                ];
            }
        }

        Yii::info('[class does not exist] Class ID:'.$id);
        return[
            'code' => 404,
            'message' => 'class does not exist'
        ];
    }

    public function actionListHomework($id):array{
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

        if($i != 0){
            $resultAverageScore = $allAverageScores/$i;
        }
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

        // Yii::$app->GradelyComponent->welcome();
        // exit;

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


    public function actionRemoveChildClass($id){
    
        $getStudent = StudentSchool::findOne(['student_id' => $id]);

        if(!empty($getStudent)){

            if($this->checkIfStudentInSchool($getStudent->school_id) == true){

                try{

                    $getStudent->class_id = "";
                    $getStudent->save();
                    return [
                        'code' => '200',
                        'message' => 'Student succesfully removed from class'
                    ];
                }
                catch(Exception $exception){
                    return [
                        'code' => '500',
                        'message' => $exception->getMessage()
                    ];
                }
            }

            return [
                'code' => '404',
                'message' => 'Student doesnt belong to your school',
            ];
        }
        return [
            'code' => '404',
            'message' => 'Student doesnt exist',
        ];
    }

    public function actionChangeStudentClass($id){
    
        $getStudent = StudentSchool::findOne(['student_id' => $id]);

        if(!empty($getStudent)){

            if($this->checkIfStudentInSchool($getStudent->school_id) == true){

                try{

                    $getStudent->class_id = $this->request['new_class'];
                    $getStudent->save();
                    return [
                        'code' => '200',
                        'message' => 'Student class succesfully updated'
                    ];
                }
                catch(Exception $exception){
                    return [
                        'code' => '500',
                        'message' => $exception->getMessage()
                    ];
                }
            }

            return [
                'code' => '404',
                'message' => 'Student doesnt belong to your school',
            ];
        }
        return [
            'code' => '404',
            'message' => 'Student doesnt exist',
        ];
    }

    public function actionGetClassDetails($id){
        
        $getClassDetails = Classes::findOne(['id' => $id,'school_id' => Utility::getSchoolId()]);

        if(!empty($getClassDetails)){
            return [
                'code' => '200',
                'message' => 'Class details succesfully listed',
                'data' => $getClassDetails
            ];
        }
        return [
            'code' => '200',
            'message' => 'It seems class ID provided doesnt belong to this school',
        ];
     }

    public function actionListStudentsClass($id){

    $getStudents =  User::find()
                    ->select('user.*')
                    ->leftJoin('student_school', '`student_school`.`student_id` = `user`.`id`')
                    ->where(['student_school.class_id' => $id])
                    ->where(['student_school.school_id' => Utility::getSchoolId()])
                    ->all();
        return [
            'code' => '200',
            'message' => 'student list successfull',
            'data' => $getStudents
        ];
    }

    
}