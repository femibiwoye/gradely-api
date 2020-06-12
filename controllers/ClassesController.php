<?php

namespace app\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\utility\Utility;
use app\models\{User,Homeworks,SchoolTeachers,TutorSession,QuizSummary,QuizSummaryDetails,Schools,Classes,GlobalClass,TeacherClass,Questions};
use yii\db\Expression;
/**
 * Class controller
 */
class ClassesController extends ActiveController
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
            [
              'class' => \yii\ filters\ ContentNegotiator::className(),
              //'only' => ['index', 'view'],
              'formats' => [
                'application/json' => \yii\ web\ Response::FORMAT_JSON,
              ],
  
  
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['generate-class'],
                'only' => ['create-class'],
                'only' => ['view-class'],
                'only' => ['delete-class'],
                'only' => ['list-parents'],
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
}