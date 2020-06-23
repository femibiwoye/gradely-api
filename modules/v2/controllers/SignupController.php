<?php

namespace app\modules\v2\controllers;


use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\modules\v2\models\{Schools,User,Login,Parents,UserProfile};
use app\modules\v2\helpers\Utility;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

/**
 * Invite controller
 */
class SignupController extends ActiveController {
    public $modelClass = 'api\v2\models\User';

    public function behaviors() {
    $behaviors = parent::behaviors();

    //For CORS
    $auth = $behaviors['authenticator'];
    unset($behaviors['authenticator']);
    $behaviors['corsFilter'] = [
      'class' => \yii\filters\Cors::className(),
    ];
    $behaviors['authenticator'] = $auth;

    $behaviors['authenticator'] = [
      'class' => CompositeAuth::className(),
      'authMethods' => [
        HttpBasicAuth::className(),
        HttpBearerAuth::className(),
        QueryParamAuth::className(),
      ],
      'except' => ['options'],
    ];

    return $behaviors;
  }

  public function actions() {
    $actions = parent::actions();
    unset($actions['create']);
    unset($actions['update']);
    unset($actions['delete']);
    unset($actions['index']);
    unset($actions['view']);
    return $actions;
  }

  public function actionCreate() {
    
  }
}