<?php

namespace app\modules\v1\school\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v1\utility\Utility; 
use app\modules\v1\models\{User,Homeworks,SchoolTeachers,TutorSession,QuizSummary,QuizSummaryDetails,Schools,Classes,GlobalClass,TeacherClass,Questions};
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
                    'add-students' => ['post']
                ],
            ],
            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => [
                            'add-students'
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


    public function actionAddStudents(){

        $user = new User(['scenario' => User::SCENARIO_SETTINGS_UPDATE_SUBJECT]);
        $user->attributes = \Yii::$app->request->post();
        if ($model->validate()) { 
            //$user = new User();
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
        return $model->errors;
    }
}