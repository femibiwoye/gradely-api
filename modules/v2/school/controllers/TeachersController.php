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
class TeachersController extends ActiveController
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
                    'invite-teachers' => ['post'],
                    'get-all-teachers' => ['get'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => [
                            'invite-teachers','get-all-teachers'
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

    public function actionInviteTeachers(){

        $inviteLogs = new InviteLogs(['scenario' => InviteLogs::SCENARIO_SCHOOL_INVITE_TEACHERS]);
        $inviteLogs->attributes = \Yii::$app->request->post();
        if ($inviteLogs->validate()) { 
            //$inviteLogs  = new InviteLogs();
            try{
                $inviteLogs->receiver_name = $this->request['name'];
                $inviteLogs->receiver_email = $this->request['email'];
                $inviteLogs->receiver_phone = $this->request['phone'];
                $inviteLogs->receiver_class = $this->request['class'];
                $inviteLogs->receiver_subject = $this->request['subject_id'];
                $inviteLogs->sender_type = 'school';
                $inviteLogs->receiver_type = 'teacher';
                $inviteLogs->sender_id = Utility::getUserId();
                $inviteLogs->save();
                return[
                    'code' => '200',
                    'message' => '',
                    'data' => $inviteLogs
                ];
            }
            catch(Exception $exception){
                return[
                    'code' => '500',
                    'message' => $exception->getMessage()
                ];
            }
        }
        return $inviteLogs->errors;

    }

    //get schools invited teachers under school
    public function actiongetAllTeachers(){

        $getAllTeachersInvited = Invitlogs::findAll(['sender_id' => Utility::getUserId()]);
        if(!empty($invitlogs)){

            return [
                'code '=> '200',
                'message '=> 'success',
                'data '=> $getAllTeachersInvited
            ];
        }
    }

    //get schools invited teachers under school
    public function actiongetSingleTeachers($id){

        $getSingleTeachersInvited = Invitlogs::findOne(['id' => $id]);
        if(!empty($invitlogs)){

            return [
                'code '=> '200',
                'message '=> 'success',
                'data '=> $getSingleTeachersInvited
            ];
        }
    }
}