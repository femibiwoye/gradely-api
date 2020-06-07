<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\{AccessControl,VerbFilter};
use yii\rest\ActiveController;
use app\models\{User,LoginForm,ContactForm};


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
        return json_encode(array('status'=>0,'error_code'=>4090,'message'=>'Bad request'),JSON_PRETTY_PRINT);
    }
}