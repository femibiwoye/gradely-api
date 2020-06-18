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
                    'view-user-profile' => ['get'],
                    'edit-user-profile' => ['put']
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['view-user-profile','edit-user-profile']
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

        $model = new User(['scenario' => User::SCENARIO_EDIT_USER_PROFILE]);
        $model->attributes = \Yii::$app->request->post();
        if ($model->validate()) {
            try{
                $getUserInfo = User::findOne(['auth_key' => Utility::getBearerToken()]);
                $getUserInfo->firstname = $this->request['firstname'];
                $getUserInfo->lastname = $this->request['lastname'];
                $getUserInfo->phone = $this->request['phone'];
                $getUserInfo->email = $this->request['email'];
                $getUserInfo->save();
                Yii::info('User profile updated successful');
                return[
                    'code' => '200',
                    'message' => 'User profile updated successful'
                ];
            }
            catch(Exception $exception){
                Yii::info('[User profile update] Error:'.$exception->getMessage().'');
                return[
                    'code' => '500',
                    'message' => $exception->getMessage()
                ];
            }
        }
        return $model->errors;
    }

}