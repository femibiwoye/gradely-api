<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\rest\ActiveController;
use app\models\User;
use app\models\LoginForm;
use frontend\models\ContactForm;


/**
 * User controller
 */
//class UserController extends Controller
 class UserController extends ActiveController
{

    public $modelClass = 'api\models\User';

    public function behaviors() {
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

    //use this to overide the default action methods,
    //wuthouth this the actionIndex in this class wont be picked rather 
    //it will pick he default action
    public function actions() {
        $actions = parent::actions();
        unset($actions['index']);
        return $actions;
    }

    public function actionIndex()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $user = User::findIdentity(1);

        echo json_encode(array('status'=>0,'error_code'=>4090,'message'=>'Bad request'),JSON_PRETTY_PRINT);
        // exit;
        //below are the three ways of returnig response status and code
        //Yii::$app->response->statusCode = 419;
        //throw new \yii\web\HttpException(404, Yii::t('app','Record not found.'));
        //throw new \yii\web\HttpException(404, 'Record not found.','76');

        //use this to get the rwquest header so you can further get the bearer authorization token
        //return Yii::$app->request->headers;

       // return $user;
    }

    public function actionIndex2()
    {
        //Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $user = User::findIdentity(1);
        //var_dump($user);
        return $user;
        //return "chinaka";
    }

    public function actionP()
    {
        echo 'hello chinaka';
    }
}