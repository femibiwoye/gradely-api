<?php

namespace app\modules\v1\school\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v1\utility\Utility; 
use app\modules\v1\models\{User,Homeworks,SchoolTeachers,TutorSession,QuizSummary,QuizSummaryDetails,Schools,Classes,GlobalClass,TeacherClass,Questions,StudentSchool,UserProfile};
use yii\db\Expression;
/**
 * Schools controller
 */
class StudentsController extends ActiveController
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
                    'add' => ['post'],
                    'list-students-class' => ['get'],
                    'get-class-details' => ['get'],
                    'change-student-class' => ['put'],
                    'remove-child-class' => ['put']
                ],
            ],
            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => [
                            'add','list-students-class','get-class-details','change-student-class','remove-child-class'
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
    }


    public function actionAdd(){

            $user = new User();
            $splitStudentName = explode(',',$this->request['student_names']);
            $user->firstname = $splitStudentName[0];
            $user->lastname = $splitStudentName[1];
            $user->setPassword($this->request['password_hash']);
            $user->type = '1';
            $user->auth_key = $user->generateAuthKey();

            if ($user->save()) {
                    $studentSchool = new StudentSchool();
                    $studentSchool->student_id = $user->id;
                    $studentSchool->school_id = Utility::getSchoolId();
                    $studentSchool->class_id = $this->request['class_id'];

                    try{
                        $studentSchool->save();

                        $userProfile = new UserProfile();
                        $userProfile->user_id = $user->id;
                        $userProfile->save();
                        return[
                            'code' =>'200',
                            'message' => 'student succesfully added',
                            'data' => $userProfile
                        ];
                    }
                    catch(Exceprion $exception){

                        return[
                            'code' =>'500',
                            'message' => $exception->getMessage()
                        ];
                    }
            }
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
                    ->innerJoin('student_school', '`student_school`.`student_id` = `user`.`id`')
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