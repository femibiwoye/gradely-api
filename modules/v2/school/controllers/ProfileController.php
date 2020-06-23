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
                    'index' => ['get'],
                    'edit-user-profile' => ['put']
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['edit-user-profile','index']
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
                $getUserInfo = User::findOne(['id' => Utility::getUserId()]);
                if(!empty($getUserInfo)){
                    $getUserInfo->firstname = $this->request['firstname'];
                    $getUserInfo->lastname = $this->request['lastname'];
                    $getUserInfo->phone = $this->request['phone'];
                    $getUserInfo->email = $this->request['email'];
                    $getUserInfo->save(false);
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