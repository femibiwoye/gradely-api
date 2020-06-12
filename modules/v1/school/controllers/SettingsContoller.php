<?php

namespace app\modules\v1\school\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\{Controller,Response};
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\utility\Utility;
use app\models\{Schools,Classes,GlobalClass,StudentSchool,User,Parents,SchoolCalendar,Homeworks,HomeworkQuestions,TutorSession,UserProfile,SchoolCurriculum,SchoolClassCurriculum};
/**
 * Schools controller
 */
class SettingsController extends ActiveController
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
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'settings-update-email' => ['put'],
                    'settings-update-password' => ['put'],
                    'settings-delete-account' => ['delete'],
                    'settings-list-curriculum' => ['get'],
                    'settings-update-curriculum' => ['put'],
                    'settings-request-new-curriculum' => ['post'],
                    'settings-list-subjects' => ['get'],
                    'settings-update-subject' => ['put'],
                    'settings-request-new-subject' => ['post'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => [
                    'settings-update-email','settings-update-password','settings-delete-account',
                    'settings-list-curriculum','settings-update-curriculum','settings-request-new-curriculum',
                    'settings-list-subjects','settings-update-subject','settings-request-new-subject'
                ],
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

public function actionSettingsUpdateEmail(){

    $model = new User();
    $user = User::findOne(['email'=> $this->request['email'], 'password' => $model->validatePassword($this->request['password'])]);
    if(!empty($user)){

        try{
            $user->email = $this->request['new_email'];
            $user->save();

            return [
                'code' => '200',
                'message' => 'Email succesfully updated'
            ];
        }
        catch(Exception $exception){
            return[
                'code' => '500',
                'message' => $exception->getMessage()
            ];
        }
    }
    return [
        'code' => '404',
        'message' => 'email or password incorrect'
    ];
}

public function actionSettingsUpdatePassword(){

    $model = new User();
    $user = User::findOne(['email'=> $this->request['email'], 'password' => $model->validatePassword($this->request['password'])]);
    if(!empty($user)){

        try{
            $user->password = $user->setPassword($this->request['password']);
            $user->save();

            return [
                'code' => '200',
                'message' => 'Password succesfully updated'
            ];
        }
        catch(Exception $exception){
            return[
                'code' => '500',
                'message' => $exception->getMessage()
            ];
        }
    }
    return [
        'code' => '404',
        'message' => 'email or password incorrect'
    ];
}

public function actionSettingsDeleteAccount(){

    $model = new User();
    $user = User::findOne(['user_id' => Utility::getUserId(), 'password' => $model->validatePassword($this->request['password'])]);
    if(!empty($user)){

        try{
            $user->status = 0;
            $user->auth_key = '';
            $user->email = $user->email.'-deleted';
            $user->phone = $user->phone.'-deleted';
            $user->save();

            return [
                'code' => '200',
                'message' => 'Password succesfully deleted'
            ];
        }
        catch(Exception $exception){
            return[
                'code' => '500',
                'message' => $exception->getMessage()
            ];
        }
    }
}

public function actionSettingsListCurriculum(){
    $getCurriculum = SchoolCurriculum::find()
                        ->select('school_curriculum.*')
                        ->leftJoin('exam_type', '`exam_type`.`id` = `school_curriculum`.`curriculum_id`')
                        ->where(['school_curriculum.school_id' => Utility::getSchoolUserId()])
                        ->all();
    if(!empty($getCurriculum)){
        return[
            'code' => '200',
            'message' => 'curriculum listing sucessful',
            'data' => $getCurriculum
        ];
    }
    return[
        'code' => '404',
        'message' => 'couldnt find any curriculum for this school',
    ];
}

public function actionSettingsUpdateCurriculum(){

$getCurriculum = SchoolCurriculum::find()->where(['school_id' => Utility::getSchoolId()])->all();
    if(!empty($getCurriculum)){

        try{
            $getCurriculum->curriculum_id = $this->request['curriculum_id'];
            $getCurriculum->save();
            return[
                'code' => '200',
                'message' => 'school curriculum successfully updted',
            ];
        }
        catch(Exception $exception){

            return[
                'code' => '500',
                'message' => $exception->getMessage()
            ];
        }
    }
    return[
        'code' => '404',
        'message' => 'couldnt find any curriculum for this school',
    ];
}

public function actionSettingsRequestNewCurriculum(){

    try{
        $sendmMailToAdmin = Yii::$app->mailer->compose()
                ->setFrom(Yii::$app->params['notificationSentFromEmail'])
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject(Yii::$app->params['newlySugestedCurriculumSubject'])
                ->setHtmlBody('
                
                    <b>Hello,</b>

                    The curriculum below was suggested
                    Curriculum Name: '.$this->request['curriculum'].'
                    Country: '.$this->request['country'].'
                    Comments: '.$this->request['comments'].'
                
                ')
                ->send();

                return[
                    'code' => '200',
                    'message' => 'Curriculum succesfully requested'
                ];
    }
    catch( Exception $exception){
        return[
            'code' => '200',
            'message' => $exception->getMessage()
        ];
    }
}


public function actionSettingsListSubjects(){

    $getSubject = SchoolSubject::findOne(['school_id' => Utility::getSchoolId()]);
    if(!empty($getSubject)){
        return[
            'code' => '200',
            'message' => 'subjects listing sucessful',
            'data' => $getSubject
        ];
    }
    return[
        'code' => '404',
        'message' => 'couldnt find any subject for this school',
    ];
}

public function actionSettingsUpdateSubject($id){

    $getSubject = SchoolSubject::findOne(['school_id' => Utility::getSchoolId(), 'id' => $id]);
    if(!empty($getSubject)){

        try{
            $getSubject->subject_id = $this->request['subject_id'];
            $getSubject->save();
            return[
                'code' => '200',
                'message' => 'school subject successfully updted',
            ];
        }
        catch(Exception $exception){

            return[
                'code' => '500',
                'message' => $exception->getMessage()
            ];
        }
    }
    return[
        'code' => '404',
        'message' => 'couldnt find the particular subject for this school',
    ];
}

public function actionSettingsRequestNewSubject(){

    try{
        $sendmMailToAdmin = Yii::$app->mailer->compose()

                ->setFrom(Yii::$app->params['notificationSentFromEmail'])
                ->setTo(Yii::$app->params['adminEmail'])
                ->setSubject(Yii::$app->params['newlySugestedSubjectSubject'])
                ->setHtmlBody('
                
                    <b>Hello,</b>

                    The subject below was suggested
                    Subject Name: '.$this->request['curriculum'].'
                    Country: '.$this->request['country'].'
                    Comments: '.$this->request['comments'].'
                
                ')
                ->send();

                return[
                    'code' => '200',
                    'message' => 'subject succesfully requested'
                ];
    }
    catch( Exception $exception){
        return[
            'code' => '200',
            'message' => $exception->getMessage()
        ];
    }
}

}