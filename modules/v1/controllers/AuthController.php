<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\filters\{AccessControl, VerbFilter, ContentNegotiator};
use yii\web\Controller;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;
use app\modules\v1\models\{Schools, Login, User, StudentSchool, SchoolTeachers, Parents, UserProfile};
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

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

    public static function allowedDomains()
    {
        return [
            '*',
            'http://localhost',
        ];
    }

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'login' => ['post'],
                    'logout' => ['post'],
                    'signup' => ['post'],
                    'forgot-password' => ['post'],
                    'recover-password' => ['post'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['logout'],
            ],

            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors'  => [
                    // restrict access to domains:
                    'Origin'                           => static::allowedDomains(),
                    'Access-Control-Request-Method'    => ['POST'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age'           => 3600,                 // Cache (seconds)
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
            return $this->getLoginResponse($model);
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

        $this->request = \yii::$app->request->post();
        $model = new User();
        $checkEmailExist = $model->findByLoginDetail($this->request['email']);
        if (!empty($checkEmailExist)) {
            try {
                $resetToken = rand(1, 100000);
                //TODO: add password reset url as environment variable
                $PasswordResetLink = Yii::$app->params['passwordResetLink'] . $resetToken;
                $checkEmailExist->password_reset_token = $resetToken;
                $checkEmailExist->save();
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
                    'data' => []
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

    public function actionRecoverPassword()
    {

        $this->request = \yii::$app->request->post();
        $user = new User();
        $checkTokenExist = $user->findOne(['password_reset_token' => $this->request['token']]);
        if (!empty($checkTokenExist)) {
            $checkTokenExist->setPassword($this->request['password']);
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

    private function getLoginResponse($model)
    {
        $user = new User();
        $authKey = $user->generateAuthKey(); //did this because i wont be able to assign authkey at the bottom after unsetting it
        $tokenExpires = $model->getUser()->token_expires;
        //unset fields that shouldnt be part of response returned
        unset($model->getUser()->auth_key);
        unset($model->getUser()->password_hash);
        unset($model->getUser()->password_reset_token);
        unset($model->getUser()->token);
        unset($model->getUser()->token_expires);
        Yii::info('[Login responce generated successfully');
        return [
            'code' => 200,
            'message' => 'Ok',
            'data' => $model->getUser(),
            'expiry' => $tokenExpires,
            'token' => $authKey
        ];
    }

    public function actionTestApi(){

        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_URL, 'http://apitest.gradely.ng/v1/auth/login');
        //curl_setopt($ch, CURLOPT_URL, 'http://apitest.gradely.ng/auth/login');
        curl_setopt($ch, CURLOPT_URL, 'http://apitest.gradely.ng/v1/auth/login');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['email'=>'chinaka@gmail.com','password'=>'chi']);
        $result = curl_exec($ch);


        // print_r($result);
        // curl_close($ch);
    }
}
