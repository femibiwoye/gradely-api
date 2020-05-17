<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\Schools;
use app\models\Login;
use app\models\ContactForm;
use app\models\User;
use app\models\StudentSchool;
use app\models\SchoolTeachers;
use app\models\Parents;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;


/**
 * Auth controller
 */
//class SiteController extends Controller
class AuthController extends ActiveController
{
    //TODO: for every request check that bearer token supplied is attached to the user


    public $modelClass = 'api\models\User';
    
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
    public function actionLogin()
    {

        $model = new Login();
        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->login()) {
            Yii::info('Login succesful');
            return $this->getLoginResponse($model);
        } else {
            $model->validate();
            Yii::info('[Login failed] Error:'.$model->validate().'');
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

    public function actionSignup()
    {
        $request = \yii::$app->request->post();
        //email is compulsary for everyone but optional for student
        if($request['type'] != 1 && empty($request['email']))
            return[
                'code' => 402,
                'message' => 'Email cannot be empty'
            ];

        if($request['type'] != 1 && empty($request['phone']))
            return[
                'code' => 402,
                'message' => 'Phone cannot be empty'
            ];

        $Loginmodel = new Login();
        $user = new User();
        $user->firstname = $request['firstname'];
        $user->lastname = $request['lastname'];
        $user->email = $request['email'];
        $user->phone = $request['phone'];
        $user->setPassword($request['password']);
        $user->type = $request['type'];
        $user->auth_key = $user->generateAuthKey();

        if ($user->save()) {
            //if type equals student
            if($request['type'] == 1){
                $studentSchool = new StudentSchool();
                $studentSchool->student_id = $user->id;
                $studentSchool->school_id = 0;
                if($studentSchool->save()){
                    //same response as login is being returned and user is automatically logged in after signup
                    $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                    return $this->getLoginResponse($Loginmodel);
                }
            }

            //if type equals teacher
            if($request['type'] == 2){
                $studentSchool = new SchoolTeachers();
                $studentSchool->teacher_id = $user->id;
                $studentSchool->school_id = 0;
                if($studentSchool->save()){
                    //same response as login is being returned and user is automatically logged in after signup
                    $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                    return $this->getLoginResponse($Loginmodel);
                }
            }

            //if type equals parent
            if($request['type'] == 3){
                $parents = new Parents();
                $parents->parent_id = $user->id;
                $parents->student_id = 0;
                if($parents->save()){
                    //same response as login is being returned and user is automatically logged in after signup
                    $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                    return $this->getLoginResponse($Loginmodel);
                }
            }

            //if type equals school
            if($request['type'] == 4){
                $school = new Schools();
                $school->user_id = $user->id;
                $school->phone = $request['phone'];
                $school->school_email = $request['email'];
                $school->contact_role = $request['role'];
                $school->name = $request['school_name'];
                if($school->save()){
                    //same response as login is being returned and user is automatically logged in after signup
                    $Loginmodel->load(Yii::$app->getRequest()->getBodyParams(), '');
                    return $this->getLoginResponse($Loginmodel);
                }
            }
        }

        else {
            $user->validate();
            Yii::info('[Login failed] Error:'.$user->validate().'');
            return $user;
        }
    }

    public function actionForgotPassword(){

        $request = \yii::$app->request->post();
        $model = new User();
        $checkEmailExist = $model->findByLoginDetail($request['email']);
        if(!empty($checkEmailExist)){

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
        }

        return[
                'code' => 402,
                'message' => "Sorry, i cant find this email"
        ];
    }

    public function actionRecoverPassword(){

        $request = \yii::$app->request->post();
        $user = new User();
        $checkTokenExist = $user->findOne(['password_reset_token' => $request['token']]);
        if(!empty($checkTokenExist)){
            $checkTokenExist->setPassword($request['password']);
            if ($checkTokenExist->save()) {
                return[
                    'code' => 200,
                    'message' => "Password reset succesful"
                ];
            }
        }

        return[
            'code' => 200,
            'message' => "Invalid token"
        ];
    }

    private function getLoginResponse($model){
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
        return[
            'code' => 200,
            'message' => 'Ok',
            'data' => ['user' => $model->getUser()],
            'expiry' => $tokenExpires,
            'token' => $authKey
        ];
    }
}
