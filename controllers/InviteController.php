<?php

namespace app\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\models\{Schools,User,InviteLog};
use app\helpers\Utility;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;



/**
 * Auth controller
 */
class InviteController extends ActiveController
{
    //TODO: for every request check that bearer token supplied is attached to the user


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

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionIndex(){

        $request = \yii::$app->request->post();
        $user = new User();

        $userId =  Utility::getUserId();
        //get email
        $findUser =  $user->findOne(['id' => $userId]);
        
        if(!empty($findUser)){

            $checkEmailExist = $user->findByLoginDetail($findUser->email);

                try{
                    $inviteLog = new InviteLog();
                    $token = rand(1,10000000);
                    //TODO: add confirm-invite url as environment variable
                    $invitationLink = 'https://gradely.com/confirm-invite/tk?='.$token;
                    $inviteLog->receiver_name = $request['receiver_name'];
                    $inviteLog->receiver_email = $request['receiver_email'];
                    $inviteLog->receiver_phone = $request['receiver_phone'];
                    $inviteLog->sender_type = $request['sender_type'];
                    $inviteLog->receiver_type = $request['receiver_type'];
                    $inviteLog->sender_id = $userId;
                    $inviteLog->token = (string) $token;
                    $inviteLog->save();
                    //sender_type e.g school, receiver type e.g parent
                   // $this->getInviteEmail($request['sender_type'],$request['receiver_type'],$invitationLink,$request['receiver_email']);
                    return [
                        'code' => 200,
                        'data' => $inviteLog
                    ];
                }
                catch(Exception  $exception){

                    return [
                        'code' => 200,
                        'message' => $exception->getMessage(),
                    ];
                }
        }

        else {

            return [
                'code' => 200,
                'message' => 'Invalid user please check bearer token',
            ];
        }
    }

    public function actionValidateInviteToken(){

        $request = \yii::$app->request->post();
        $Loginmodel = new Login();
        $inviteLog = new InviteLog();
        $user = new User();
        $checkTokenExist = $inviteLog->findOne(['token' => $request['token'],'status' => 0]);
        if(!empty($checkTokenExist)){

            $checkUserExist = User::findOne(['email' => $checkTokenExist->receiver_email]);

            //teacher to school invite
            if(empty($checkUserExist) && $checkTokenExist->sender_type ='school' && $checkTokenExist->receiver_type ='teacher'){
                try{
                    $schoolTeacher = new SchoolTeachers();
                    $schoolTeacher->teacher_id = $user->id;
                    $schoolTeacher->school_id = $checkTokenExist->sender_id;
                    $schoolTeacher->save();
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
                try{
                    return [
                        'code' =>'200',
                        'message' => '',
                        'data' => [
                            'name' =>$checkTokenExist->name,
                            'email' =>$checkTokenExist->email
                        ]
                    ];
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }
            elseif(!empty($checkUserExist) && $checkTokenExist->sender_type ='school' && $checkTokenExist->receiver_type ='teacher'){
                //if receivers email exist
                try{
                    $checkTokenExist->status = 1;
                    $checkTokenExist->save();
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }

            //student to parent invite
            if(empty($checkUserExist) && $checkTokenExist->sender_type ='student' && $checkTokenExist->receiver_type ='parent'){
                try{
                    $studentParent = new Parents();
                    $studentParent->parent_id = $user->id;
                    $studentParent->student_id = $checkTokenExist->sender_id;
                    $studentParent->save();
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }


                try{
                    return [
                        'code' =>'200',
                        'message' => '',
                        'data' => [
                            'name' =>$checkTokenExist->name,
                            'email' =>$checkTokenExist->email
                        ]
                    ];
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }
            elseif(!empty($checkUserExist) && $checkTokenExist->sender_type ='school' && $checkTokenExist->receiver_type ='teacher'){
                //if receivers email exist
                try{
                    $checkTokenExist->status = 1;
                    $checkTokenExist->save();
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }

            //parent to school invite
            if(empty($checkUserExist) && $checkTokenExist->sender_type ='parent' && $checkTokenExist->receiver_type ='school'){
                try{
                    return [
                        'code' =>'200',
                        'message' => '',
                        'data' => [
                            'name' =>$checkTokenExist->name,
                            'email' =>$checkTokenExist->email
                        ]
                    ];
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }
            elseif(!empty($checkUserExist) && $checkTokenExist->sender_type ='school' && $checkTokenExist->receiver_type ='teacher'){
                //if receivers email exist
                try{
                    $checkTokenExist->status = 1;
                    $checkTokenExist->save();
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }

            //school to parent invite
            if(empty($checkUserExist) && $checkTokenExist->sender_type ='school' && $checkTokenExist->receiver_type ='parent'){
                try{
                    return [
                        'code' =>'200',
                        'message' => '',
                        'data' => [
                            'name' =>$checkTokenExist->name,
                            'email' =>$checkTokenExist->email
                        ]
                    ];
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }
            elseif(!empty($checkUserExist) && $checkTokenExist->sender_type ='school' && $checkTokenExist->receiver_type ='teacher'){
                //if receivers email exist
                try{
                    $checkTokenExist->status = 1;
                    $checkTokenExist->save();
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }


            //student to school invite
            if(empty($checkUserExist) && $checkTokenExist->sender_type ='student' && $checkTokenExist->receiver_type ='school'){
                try{
                    $studentSchool = new StudentSchool();
                    $studentSchool->teacher_id = $user->id;
                    $studentSchool->school_id = $checkTokenExist->sender_id;
                    $studentSchool->save();
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
                try{
                    return [
                        'code' =>'200',
                        'message' => '',
                        'data' => [
                            'name' =>$checkTokenExist->name,
                            'email' =>$checkTokenExist->email
                        ]
                    ];
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }
            elseif(!empty($checkUserExist) && $checkTokenExist->sender_type ='school' && $checkTokenExist->receiver_type ='teacher'){
                //if receivers email exist
                try{
                    $checkTokenExist->status = 1;
                    $checkTokenExist->save();
                }
                catch(Exception $exception){
                    return[
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
            }
        }
    }

    private function getInviteEmail($receiverType,$invitationLink,$receiverEmail){
        $request = \yii::$app->request->post();
        Yii::$app->mailer->compose()
        ->setFrom('invitation@gradely.com')
        ->setTo($receiverEmail)
        ->setSubject('Invitation to signup as a '.$receiverType.' on Gradely.com')
        ->setHtmlBody('
        
            <b>Hello,</b>

            kindly click the link below to register
            '.$invitationLink.'
        
        ')
        ->send();
        return;
    }
}