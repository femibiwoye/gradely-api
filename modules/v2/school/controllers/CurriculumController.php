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
 * Schools controller
 */
class CurriculumController extends ActiveController
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
                    'index' => ['get'],
                    'update-curriculum' => ['put'],
                    'request-new' => ['post'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['index','update-curriculum','request-new']
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

    public function actionIndex(){
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

    public function actionUpdateCurriculum(){

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

    public function actionRequestNew(){

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
}