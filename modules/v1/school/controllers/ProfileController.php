<?php

namespace app\modules\v1\school\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\modules\v1\models\{Schools,User,InviteLog};
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
                    'view-school-profile' => ['get'],
                    'edit-school-profile' => ['put']
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['view-school-profile','edit-school-profile']
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
    public function actionViewSchoolProfile(){

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
}