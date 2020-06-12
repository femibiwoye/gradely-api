<?php

namespace app\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\models\{Schools,User,InviteLog};
use app\utility\Utility;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;



/**
 * Invite controller
 */
class InviteController extends ActiveController
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

}