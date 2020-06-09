<?php

namespace app\modules\v1\controllers;


use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use app\modules\v1\models\{Schools,User,Login,Parents};
use app\modules\v1\helpers\Utility;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

/**
 * Invite controller
 */
class SignupController extends ActiveController
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
                    'index' => ['post']
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['index'],
                'only' => ['validate-invite-token'],
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

    public function actionParent()
    {
        
      $user = new User(['scenario' => User::SCENARIO_PARENT_SIGNUP]);

      $user->attributes = \Yii::$app->request->post();

      if ($user->validate()) { 

          if (!User::find()->where(['email' => $this->request['email']])->exists()) {

              $Loginmodel = new Login();
              $user = new User();
              $user->firstname = $this->request['firstname'];
              $user->lastname = $this->request['lastname'];
              $user->email = $this->request['email'];
              $user->setPassword($this->request['password_hash']);
              $user->type = 3;
              $user->auth_key = $user->generateAuthKey();

              if ($user->save()) {

                  try {
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


  public function actionSchool()
  {
    $user = new User(['scenario' => User::SCENARIO_SCHOOL_SIGNUP]);

    $user->attributes = \Yii::$app->request->post();

    if ($user->validate()) { 
        if (!User::find()->where(['email' => $this->request['email']])->exists()) {

            $Loginmodel = new Login();
            // $user = new User();
            $user->firstname = $this->request['firstname'];
            $user->lastname = $this->request['lastname'];
            $user->email = $this->request['email'];
            $user->setPassword($this->request['password_hash']);
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

    return $user->errors;
  }

  public function actionStudent(){

    $user = new User(['scenario' => User::SCENARIO_STUDENT_SIGNUP]);

        $user->attributes = \Yii::$app->request->post();

        if ($user->validate()) { 

            if (!User::find()->where(['email' => $this->request['email']])->exists()) {

                $Loginmodel = new Login();
                $user = new User();
                $user->firstname = $this->request['firstname'];
                $user->lastname = $this->request['lastname'];
                $user->email = $this->request['email'];
                $user->setPassword($this->request['password_hash']);
                $user->type = 3;
                $user->auth_key = $user->generateAuthKey();

                if ($user->save()) {

                    try {
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

  public function actionTeacher(){

    $user = new User(['scenario' => User::SCENARIO_TEACHER_SIGNUP]);

        $user->attributes = \Yii::$app->request->post();

        if ($user->validate()) { 

            if (!User::find()->where(['email' => $this->request['email']])->exists()) {

                $Loginmodel = new Login();
                $user = new User();
                $user->firstname = $this->request['firstname'];
                $user->lastname = $this->request['lastname'];
                $user->email = $this->request['email'];
                $user->setPassword($this->request['password_hash']);
                $user->type = 3;
                $user->auth_key = $user->generateAuthKey();

                if ($user->save()) {

                    try {
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

}