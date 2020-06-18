<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\filters\{AccessControl, VerbFilter, ContentNegotiator};
use yii\web\Controller;
//use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;
use app\modules\v1\models\{Schools, Login, User, StudentSchool, SchoolTeachers, Parents, UserProfile};
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v1\helpers\Utility;

/**
 * Auth controller
 */
class AuthController extends ActiveController
{
    public $modelClass = 'api\v1\models\User';

    private $request;

    public function beforeAction($action)
    {
        $this->request = \yii::$app->request->post();
        return parent::beforeAction($action);
    }


    /**
     * It is important verb is used to control HTTP request type to accept.
     * {@inheritdoc}
     * @return array
     */
    public function behaviors()
    {
        return [

            'corsFilter' => [

                'class' => \yii\filters\Cors::className(),

                'cors' => [

                    // restrict access to

                    //'Origin' => ['http://localhost', 'http://www.myserver.com'],

                    'Origin' => ['http://localhost','*'],

                    'Access-Control-Request-Method' => ['POST', 'PUT', 'GET'],

                    'Access-Control-Request-Headers' => ['X-Wsse'],

                    // Allow only headers 'X-Wsse'

                    'Access-Control-Allow-Credentials' => true,

                    // Allow OPTIONS caching

                    'Access-Control-Max-Age' => 3600,

                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.

                    'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],

                ],

            ],



            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'login' => ['post'],
                    'logout' => ['post'],
                    'signup' => ['post'],
                    'forgot-password' => ['post'],
                    'recover-password' => ['put'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['logout'],
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

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {

        $model = new Login();

        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->login()) {
            Yii::info('Login succesful');
            return Utility::getLoginResponse($model);
            Yii::$app->response->statusCode = 200;
        } else {
            $model->validate();
            Yii::info('[Login failed] Error:' . $model->validate() . '');
            return $model;
        }
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
    }

    public function actionForgotPassword()
    {
        $user = new User(['scenario' => User::SCENARIO_FORGOT_PASSWORD]);
        $user->attributes = \Yii::$app->request->post();
        if ($user->validate()) { 
            $checkEmailExist = $user->findByLoginDetail($this->request['email']);
            //var_dump($checkEmailExist);
            if (!empty($checkEmailExist)) {
                try {
                    $resetToken = rand(1, 100000);
                    //TODO: add password reset url as environment variable
                    $PasswordResetLink = Yii::$app->params['passwordResetLink'] . $resetToken;
                    $checkEmailExist->password_reset_token = $resetToken;
                    $checkEmailExist->save(false);
                    Yii::$app->mailer->compose()
                        ->setFrom(Yii::$app->params['notificationSentFromEmail'])
                        ->setTo($this->request['email'])
                        ->setSubject(Yii::$app->params['passwordResetEmailSubject'])
                        ->setHtmlBody(Yii::$app->params['passwordResetEmailBody'] . $PasswordResetLink)
                        ->send();
                    //if the user email exist update the user table by generating a
                    //password reset token, the reset token should then be added to the url sent to the users email
                    //so when user clicks on the forgot password link the token is compared with whats on the users
                    //password rest token field
                    $response = [
                        'code' => 200,
                        'message' => "Reset Link sent to email",
                    ];
                    return $response;
                } catch (Exception  $exception) {

                    return [
                        'code' => 200,
                        'message' => $exception->getMessage(),
                    ];
                }
            }
            return [
                'code' => 200,
                'message' => "Sorry, i cant find this email"
            ];
        }
        return $user->errors;
    }

    public function actionRecoverPassword()
    {

        $user = new User(['scenario' => User::SCENARIO_RECOVER_PASSWORD]);
        $user->attributes = \Yii::$app->request->post();
        if ($user->validate()) { 
            $checkTokenExist = $user->findOne(['password_reset_token' => $this->request['token']]);
            if (!empty($checkTokenExist)) {
                $checkTokenExist->setPassword($this->request['password_hash']);
                if ($checkTokenExist->save()) {
                    return [
                        'code' => 200,
                        'message' => "Password reset succesful"
                    ];
                }
            }

            return [
                'code' => 200,
                'message' => "Invalid token"
            ];
        }
        return $user->errors;
    }

    public function actionTestApi(){

        $ch = curl_init();
        $data = '';
        //curl_setopt($ch, CURLOPT_URL, 'http://apitest.gradely.ng/v1/auth/login');
        //curl_setopt($ch, CURLOPT_URL, 'http://apitest.gradely.ng/auth/login');
        curl_setopt($ch, CURLOPT_URL, 'http://apitest.gradely.ng/v1/auth/login');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        $result = curl_exec($ch);
        
        
        print_r($result);
        curl_close($ch);
    }
}

