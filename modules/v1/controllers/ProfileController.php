<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\modules\v1\models\{User};
use app\modules\v1\helpers\Utility;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

/**
 * Invite controller
 */
class ProfileController extends ActiveController
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
                    'validate-invite-token' => ['post']
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['index'],
                'only' => ['validate-invite-token'],
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


    public function actionViewUserProfile(){

        $getLoginResponse = Utility::getLoginResponseByBearerToken();
        if(!empty($getLoginResponse)){
            return $getLoginResponse;
        }
    }

    public function actionEditUserProfile(){

        try{
            $getUserInfo = User::findOne(['auth_key' => Utility::getBearerToken()]);
            $getUserInfo->firstname = $this->request['firstname'];
            $getUserInfo->lastname = $this->request['lastname'];
            $getUserInfo->phone = $this->request['phone'];
            $getUserInfo->email = $this->request['email'];
            $getUserInfo->save();
            Yii::info('School user profile update successful');
            return[
                'code' => '200',
                'message' => 'School user profile update successful'
            ];
        }
        catch(Exception $exception){
            Yii::info('[School user profile update] Error:'.$exception->getMessage().'');
            return[
                'code' => '500',
                'message' => $exception->getMessage()
            ];
        }
    }

}