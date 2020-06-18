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
class SchoolController extends ActiveController
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
                    'edit-school-profile' => ['put'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => [
                    'index','edit-school-profile'
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

    //get schools details from the school table
    public function actionIndex(){

        $getUserId = Utility::getUserId();
        $getSchoolInfo = Schools::findOne(['user_id' => $getUserId]);
        if(!empty($getSchoolInfo)){
            return [
                'code '=> '200',
                'message '=> 'success',
                'data '=> $getSchoolInfo
            ];
        }
        return [
            'code '=> '200',
            'message '=> 'School not found'
        ];
    }

    public function actionEditSchoolProfile(){

        $model = new Schools(['scenario' => Schools::SCENERIO_EDIT_SCHOOL_PROFILE]);

        $model->attributes = \Yii::$app->request->post();

        if ($model->validate()) { 
            try{
                $getSchoolInfo = Schools::findOne(['user_id' => Utility::getUserId()]);
                if(!empty($getSchoolInfo)){
                    $getSchoolInfo->name = $this->request['name'];
                    $getSchoolInfo->about = $this->request['about'];
                    $getSchoolInfo->address = $this->request['address'];
                    $getSchoolInfo->city = $this->request['city'];
                    $getSchoolInfo->state = $this->request['state'];
                    $getSchoolInfo->country = $this->request['country'];
                    $getSchoolInfo->postal_code = $this->request['postal_code'];
                    $getSchoolInfo->website = $this->request['website'];
                    $getSchoolInfo->phone = $this->request['phone'];
                    $getSchoolInfo->school_email = $this->request['school_email'];
                    $getSchoolInfo->save();
                    Yii::info('School profile update successful');
                    return[
                        'code' => '200',
                        'message' => 'update successful'
                    ];
                }

                return[
                    'code' => '200',
                    'message' => 'school not found'
                ];

            }
            catch(Exception $exception){
                Yii::info('[School profile update] Error:'.$exception->getMessage().'');
                return[
                    'code' => '500',
                    //'message' => $exception->getMessage()
                ];
            }
        }
        return $model->errors;
    }
}