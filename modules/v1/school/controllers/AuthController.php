<?php

namespace app\modules\v1\school\controllers;

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
                'only' => [''],
            ],
            'corsFilter' => [
                'class'=>\yii\filters\Cors::className()
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


    public function actionSignupStudent(){

        $user = new User(['scenario' => User::SCENARIO_STUDENT_SIGNUP]);

        $user->attributes = \Yii::$app->request->post();

        if ($user->validate()) { 
            if (!User::find()->where(['email' => $this->request['email']])->exists()) {

                $Loginmodel = new Login();
                // $user = new User();
                $user->firstname = $this->request['firstname'];
                $user->lastname = $this->request['lastname'];
                $user->email = $this->request['email'];
                $user->setPassword($this->request['password']);
                $user->type = 1;
                $user->auth_key = $user->generateAuthKey();

                if ($user->save()) {

                    try {
                        $school->save();

                        $userProfile = new UserProfile();
                        $userProfile->user_id = $user->id;
                        $userProfile->save();

                        //same response as login is being returned and user is automatically logged in after signup
                        $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                        return $this->getLoginResponse($Loginmodel);

                    } catch (Exceprion $exception) {

                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                    //same response as login is being returned and user is automatically logged in after signup
                    $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                    return $this->getLoginResponse($Loginmodel);
                } else {
                    $user->validate();
                    Yii::info('[Login failed] Error:' . $user->validate() . '');
                    return $user;
                }
            } else {
                return [
                    'code' => '400',
                    'message' => 'email already exist'
                ];
            }
        }

        return $user->errors;
    }

    public function actionSignupTeacher(){

        if (!User::find()->where(['email' => $this->request['email']])->exists()) {

            $Loginmodel = new Login();
            $user = new User();
            $user->firstname = $this->request['firstname'];
            $user->lastname = $this->request['lastname'];
            $user->email = $this->request['email'];
            $user->phone = $this->request['phone'];
            $user->setPassword($this->request['password']);
            $user->type = 2;
            $user->auth_key = $user->generateAuthKey();

            if ($user->save()) {

                    try {
                        $school->save();

                        $userProfile = new UserProfile();
                        $userProfile->user_id = $user->id;
                        $userProfile->save();

                        //same response as login is being returned and user is automatically logged in after signup
                        $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                        return $this->getLoginResponse($Loginmodel);

                    } catch (Exceprion $exception) {

                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }
                //same response as login is being returned and user is automatically logged in after signup
                $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                return $this->getLoginResponse($Loginmodel);
            } else {
                $user->validate();
                Yii::info('[Login failed] Error:' . $user->validate() . '');
                return $user;
            }
        } else {
            return [
                'code' => '400',
                'message' => 'email already exist'
            ];
        }
    }

    public function actionSignupParent(){

        $this->request = \yii::$app->request->post();

        if (!User::find()->where(['email' => $this->request['email']])->exists()) {

            $Loginmodel = new Login();
            $user = new User();
            $user->firstname = $this->request['firstname'];
            $user->lastname = $this->request['lastname'];
            $user->email = $this->request['email'];
            $user->setPassword($this->request['password']);
            $user->type = 3;
            $user->auth_key = $user->generateAuthKey();

            if ($user->save()) {

                try {
                    $school->save();

                    $userProfile = new UserProfile();
                    $userProfile->user_id = $user->id;
                    $userProfile->save();

                    //same response as login is being returned and user is automatically logged in after signup
                    $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                    return $this->getLoginResponse($Loginmodel);

                } catch (Exceprion $exception) {

                    return [
                        'code' => '200',
                        'message' => $exception->getMessage()
                    ];
                }
                //same response as login is being returned and user is automatically logged in after signup
                $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                return $this->getLoginResponse($Loginmodel);
            } else {
                $user->validate();
                Yii::info('[Login failed] Error:' . $user->validate() . '');
                return $user;
            }
        } else {
            return [
                'code' => '400',
                'message' => 'email already exist'
            ];
        }
    }

    public function actionSignupSchool()
    {
        $this->request = \yii::$app->request->post();

        if (!User::find()->where(['email' => $this->request['email']])->exists()) {

            $Loginmodel = new Login();
            $user = new User();
            $user->firstname = $this->request['firstname'];
            $user->lastname = $this->request['lastname'];
            $user->email = $this->request['email'];
            $user->phone = $this->request['phone'];
            $user->setPassword($this->request['password']);
            $user->type = 4;
            $user->auth_key = $user->generateAuthKey();

            if ($user->save()) {
                    $school = new Schools();
                    $school->user_id = $user->id;
                    $school->phone = $this->request['phone'];
                    $school->school_email = $this->request['email'];
                    $school->contact_role = $this->request['role'];
                    $school->name = $this->request['school_name'];

                    try {
                        $school->save();

                        $userProfile = new UserProfile();
                        $userProfile->user_id = $user->id;
                        $userProfile->save();

                        //same response as login is being returned and user is automatically logged in after signup
                        $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                        return $this->getLoginResponse($Loginmodel);

                    } catch (Exceprion $exception) {

                        return [
                            'code' => '200',
                            'message' => $exception->getMessage()
                        ];
                    }

                //same response as login is being returned and user is automatically logged in after signup
                $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                return $this->getLoginResponse($Loginmodel);
            } else {
                $user->validate();
                Yii::info('[Login failed] Error:' . $user->validate() . '');
                return $user;
            }
        } else {
            return [
                'code' => '400',
                'message' => 'email already exist'
            ];
        }
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
}
