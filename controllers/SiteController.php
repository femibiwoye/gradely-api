<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\Login;
use app\models\ContactForm;
use app\models\User;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

use yii\filters\ContentNegotiator;



//class SiteController extends Controller
class SiteController extends ActiveController
{
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
            
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                    'signup' => ['post'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['dashboard'],
            ],

            'access' => [
                'class' => AccessControl::className(),
                'only' => ['dashboard'],
                'rules' => [
                    [
                        'actions' => ['dashboard'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
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


        return [
            [
              'class' => \yii\ filters\ ContentNegotiator::className(),
              //'only' => ['index', 'view'],
              'formats' => [
                'application/json' => \yii\ web\ Response::FORMAT_JSON,
              ],
  
  
            ],
            
          ];


    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        // if (!Yii::$app->user->isGuest) {
        //     return $this->goHome();
        // }
        
        // $request = \yii::$app->request->post();

        // $model = new LoginForm();

        // if($request){


        // }

        // if ($model->load(Yii::$app->request->post()) && $model->login()) {
        //     return $this->goBack();
        // }

        // $model->password = '';
        // return $this->render('login', [
        //     'model' => $model,
        // ]);



        $model = new Login();
        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->login()) {
            return ['access_token' => Yii::$app->user->identity->getAuthKey()];
        } else {
            $model->validate();
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

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        //return $this->render('about');
        echo phpinfo();
    }


    public function actionRegister()

	{
		//$model=new RegisterForm;

		$newUser = new User();

		// if it is ajax validation request

		// if(isset($_POST['ajax']) && $_POST['ajax']==='login-form')

		// {

		// 	echo CActiveForm::validate($model);

		// 	Yii::app()->end();

		// }


		// collect user input data

		//if(isset($_POST['RegisterForm']))

		//{

            $request = \yii::$app->request->post();
            //var_dump($request['email']);

			// $model->attributes=$_POST['RegisterForm'];

			$newUser->username = $request['username'];

			$newUser->password = $request['password'];

			$newUser->email = $request['email'];

			$newUser->joined = date('Y-m-d');

				

			// if($newUser->save()) {

			// 	//$identity=new UserIdentity($newUser->username,$model->password);

			// 	//$identity->authenticate();

			// 	//Yii::app()->user->login($identity,0);

			// 	//redirect the user to page he/she came from

			// 	//$this->redirect(Yii::app()->user->returnUrl);
            // }
            //return array('status' => true, 'data'=> 'Student record is successfully updated');
            // $request = \yii::$app->request->post();
            //     var_dump($request['email']);
				

		//}

		// display the register form

		//$this->render('register',array('model'=>$model));

	}


    public function actionSignup()
    {

        $request = \yii::$app->request->post();
        $user = new User();
        $user->username = $request['username'];
        $user->email = $request['email'];
        //$user->setPassword($request['password']);
        // $user->setPassword($request['password_hash']);
        // $user->type = $request['type'];
        // $user->generateAuthKey();
        if ($user->save()) {
            return 'good';
        }

        else {
            $user->validate();
            return $user;
        }

    }

    public function actionDashboard()
    {
        $response = [
            'username' => Yii::$app->user->identity->username,
            'access_token' => Yii::$app->user->identity->getAuthKey(),
        ];
        return $response;
    }

}
