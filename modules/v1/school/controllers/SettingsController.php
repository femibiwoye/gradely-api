<?php

namespace app\modules\v1\school\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\{Controller,Response};
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v1\utility\Utility; 
use app\modules\v1\models\{Schools,Classes,GlobalClass,StudentSchool,User,Parents,SchoolCalendar,Homeworks,HomeworkQuestions,TutorSession,UserProfile,SchoolCurriculum,SchoolClassCurriculum};
/**
 * Settings controller
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
                    'update-email' => ['put'],
                    'update-password' => ['put'],
                    'delete-account' => ['delete'],
                    'list-curriculum' => ['get'],
                    'update-curriculum' => ['put'],
                    'request-new-curriculum' => ['post'],
                    'list-subjects' => ['get'],
                    'update-subject' => ['put'],
                    'request-new-subject' => ['post'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => [
                    'update-email','update-password','delete-account',
                    'list-curriculum','update-curriculum','request-new-curriculum',
                    'list-subjects','update-subject','request-new-subject'
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

public function actionUpdateEmail(){

    $model = new User(['scenario' => User::SCENERIO_UPDATE_SCHOOL_EMAIL]);
    $model->attributes = \Yii::$app->request->post();
    if ($model->validate()) { 
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
    return $model->errors;
}

public function actionUpdatePassword(){

    $model = new User(['scenario' => User::SCENERIO_UPDATE_SCHOOL_PASSWORD]);
    $model->attributes = \Yii::$app->request->post();
    if ($model->validate()) { 
        $user = User::findOne(['email'=> $this->request['email'], 'password' => $model->validatePassword($this->request['password'])]);
        if(!empty($user)){

            try{
                $user->password_hash = $user->setPassword($this->request['password']);
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
    return $model->errors;
}

public function actionDeleteAccount(){

    $model = new User(['scenario' => User::SCENERIO_SETTINGS_DELETE_ACCOUNT]);
    $model->attributes = \Yii::$app->request->post();
    if ($model->validate()) { 
        $user = User::findOne(['user_id' => Utility::getUserId(), 'password' => $model->validatePassword($this->request['password_hash'])]);
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
    return $model->errors;
}

public function actionListCurriculum(){
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

public function actionUpdateCurriculum($id){

    $model = new SchoolCurriculum(['scenario' => SchoolCurriculum::SCENERIO_SETTINGS_UPDATE_CURRICULUM]);
    $model->attributes = \Yii::$app->request->post();
    if ($model->validate()) { 
        $getCurriculum = SchoolCurriculum::find()->where(['school_id' => Utility::getSchoolId(),'curriculum_id' =>$id])->all();
        if(!empty($getCurriculum)){

            try{
                $getCurriculum->curriculum_id = $this->request['new_curriculum_id'];
                $getCurriculum->save(false);
                return[
                    'code' => '200',
                    'message' => 'school curriculum successfully updated',
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
            'message' => 'couldnt find the specific curriculum id for this school',
        ];
    }
    return $model->errors;
}

public function actionRequestNewCurriculum(){

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


public function actionListSubjects(){

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

public function actionUpdateSubject($id){

    $model = new SchoolSubject(['scenario' => SchoolSubject::SCENERIO_SETTINGS_UPDATE_SUBJECT]);
    $model->attributes = \Yii::$app->request->post();
    if ($model->validate()) { 
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
    return $model->errors;
}

public function actionRequestNewSubject(){

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