<?php

namespace app\modules\v1\school\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\modules\v1\models\{Schools,User,InviteLog};
use app\modules\v1\utility\Utility; 
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

/**
 * Invite controller
 */
class InviteController extends ActiveController
{
    public $modelClass = 'api\v1\models\User';

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
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'index' => ['post'],
                    'validate-invite-token' => ['post'],
                    'teacher' => ['get'],
                    'actionDetailedTeacher' => ['get'],
                    'update' => ['put']
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['index','validate-invite-token','teacher','actionDetailedTeacher','update']
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

        $user = new User();

        $inviteLog = new InviteLog(['scenario' => InviteLog::SCENARIO_INVITE]);

        $inviteLog->attributes = \Yii::$app->request->post();

        if ($inviteLog->validate()) { 

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
                        $inviteLog->receiver_name = $this->request['receiver_name'];
                        $inviteLog->receiver_email = $this->request['receiver_email'];
                        $inviteLog->receiver_phone = $this->request['receiver_phone'];
                        $inviteLog->sender_type = $this->request['sender_type'];
                        $inviteLog->receiver_type = $this->request['receiver_type'];
                        $inviteLog->sender_id = $userId;
                        $inviteLog->token = (string) $token;
                        $inviteLog->save();
                        //sender_type e.g school, receiver type e.g parent
                    // $this->getInviteEmail($this->request['sender_type'],$this->request['receiver_type'],$invitationLink,$this->request['receiver_email']);
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
        }

        else {

            return [
                'code' => 200,
                'message' => 'Invalid user please check bearer token',
            ];
        }
        return $inviteLog->errors;
    }

    public function actionValidateInviteToken(){

        $inviteLog = new InviteLog(['scenario' => InviteLog::SCENARIO_VALIDATE_INVITE_TOKEN]);

        $inviteLog->attributes = \Yii::$app->request->post();

        if ($inviteLog->validate()) { 
            $Loginmodel = new Login();
            $inviteLog = new InviteLog();
            $user = new User();
            $checkTokenExist = $inviteLog->findOne(['token' => $this->request['token'],'status' => 0]);
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
        return $inviteLog->errors;
    }

    //get all invited teachers
    public function actionTeacher(){
        
        $getInviteLog = InviteLog::findAll(['sender_id' => Utility::getUserId(),'receiver_type' =>'teacher']);

        if(isset($_GET['status'])){

            $getInviteLog = InviteLog::findAll(['sender_id' => Utility::getUserId(),'receiver_type' =>'teacher','status' => $_GET['status']]);
        }

        return [
            'code' =>'200',
            'message' => 'Listing Successful',
            'data' => $getInviteLog
        ];
    }

    //get detailed invited teachers
    public function actionDetailedTeacher($id){
        
        $getInviteLog = InviteLog::findAll(['id' => $id]);

        return [
            'code' =>'200',
            'message' => 'Listing Successful',
            'data' => $getInviteLog
        ];
    }

    public function actionUpdate(){

        $model = new InviteLog(['scenario' => InviteLog::SCENARIO_UPDATE_INVITE]);

        $model->attributes = \Yii::$app->request->post();

        if ($model->validate()) {

            if($this->request['type'] == 'one'){

                $getInviteLog = InviteLog::findOne(['id' => $this->request['invitation_id'],'receiver_type' =>'teacher']);
                $getInviteLog->status = $this->request['status'];
                $getInviteLog->save(false);
                return [
                    'code' =>'200',
                    'message' => 'Update Successful',
                ];
            }
            
            elseif($this->request['type'] == 'all'){

                $getInviteLogs = InviteLog::findAll(['sender_id' => Utility::getUserId(),'receiver_type' =>'teacher']);

                foreach($getInviteLogs as $getInviteLog ){

                    $getInviteLog->status = $this->request['status'];
                    $getInviteLog->save(false);
                }
                return [
                    'code' =>'200',
                    'message' => 'Update Successful',
                ];
            }
        }
        return $model->errors;
    }

    private function getInviteEmail($receiverType,$invitationLink,$receiverEmail){
        Yii::$app->mailer->compose()
        ->setFrom(Yii::$app->params['invitationSentFromEmail'])
        ->setTo($receiverEmail)
        ->setSubject(Yii::$app->params['invitationEmailSubject'])
        ->setHtmlBody(Yii::$app->params['invitationEmailBody'].$invitationLink)
        ->send();
        return;
    }

}