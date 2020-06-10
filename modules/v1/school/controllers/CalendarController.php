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
class CalendarController extends ActiveController
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
                    'view-school-calendar' => ['get'],
                    'edit-school-calendar' => ['post'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['index','view-school-calendar,edit-school-calendar']
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

    //get schools details from the school table
    public function actionViewSchoolCalendar(){

        $getUserId = Utility::getUserId();
        $getSchoolInfo = Schools::findOne(['user_id' => $getUserId]);
        if(!empty($getSchoolInfo)){

            $getSchoolCalendarInfo = SchoolCalendar::findOne(['school_id' => $getSchoolInfo->id]);

            return [
                'code '=> '200',
                'message '=> 'success',
                'data '=> $getSchoolCalendarInfo
            ];
        }
        return [
            'code '=> '200',
            'message '=> 'school calendar not found'
        ];
    }


    public function actionEditSchoolCalendar(){

        try{
            $getUserId = Utility::getUserId();
            $getSchoolInfo = Schools::findOne(['user_id' => $getUserId]);
            if(!empty($getSchoolInfo)){
                $getSchoolCalendarInfo = SchoolCalendar::findOne(['school_id' => $getSchoolInfo->id]);
                $getSchoolCalendarInfo->session_name = $this->request['session_name'];
                $getSchoolCalendarInfo->year = $this->request['year'];
                $getSchoolCalendarInfo->first_term_start = $this->request['first_term_start'];
                $getSchoolCalendarInfo->first_term_end = $this->request['first_term_end'];
                $getSchoolCalendarInfo->second_term_start = $this->request['second_term_start'];
                $getSchoolCalendarInfo->second_term_end = $this->request['second_term_end'];
                $getSchoolCalendarInfo->third_term_start = $this->request['third_term_start'];
                $getSchoolCalendarInfo->third_term_end = $this->request['third_term_end'];
                $getSchoolCalendarInfo->status = $this->request['status'];
                $getSchoolCalendarInfo->save();
                Yii::info('School profile calendar update successful');
                return[
                    'code' => '200',
                    'message' => 'School profile calendar update successful'
                ];
            }
            return[
                'code' => '200',
                'message' => 'school not found'
            ];
        }
        catch(Exception $exception){
            Yii::info('[School profile calendar update] Error:'.$exception->getMessage().'');
            return[
                'code' => '500',
                //'message' => $exception->getMessage()
            ];
        }
    }
}