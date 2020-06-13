<?php

namespace app\modules\v1\school\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v1\utility\Utility; 
use app\modules\v1\models\{User,Homeworks,SchoolTeachers,TutorSession,QuizSummary,QuizSummaryDetails,Schools,Classes,GlobalClass,TeacherClass,Questions};
use yii\db\Expression;
/**
 * Schools controller
 */
class ParentsController extends ActiveController
{
    public $modelClass = 'api\models\User';
    
    /**
     * {@inheritdoc}
     */

    private $request;

    public function beforeAction($action)
    {
        $this->request = \yii::$app->request->post();
        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'list-parents' => ['get']
                ],
            ],
            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => [
                            'list-parents'
                        ]
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

    public function actionListParents(){

        //get all parents
        $getAllParents = Parents::find()
                ->select('parents.*')
                ->innerJoin('student_school', '`student_school`.`student_id` = `parents`.`student_id`')
                ->where(['student_school.school_id' => Utility::getSchoolId()])
                ->all();

        $parentChildren = [];
        if(!empty($getAllParents)){

            $storeParentId = []; $allChildrenInfo = "";
            $keys = []; $getparentChildrenInfo = []; $getAllChildren = [];  $getSingleParentId = []; $getParentUserInfos = []; $i =1;
            foreach($getAllParents as $key => $getParent){


                //if(in_array($getParent->parent_id, $storeParentId)){
                    $getParentUserInfos[] = User::find()->where(['id' => $getParent->parent_id])->one();


                    // $storeParentId[] = $getParent->parent_id;
                    // if(in_array('15', $storeParentId)){

                    //     continue;
                    // }

                    //get all children of parent
                    $getAllChildren[] = Parents::findAll(['parent_id' => $getParent->parent_id]);

                    

                    foreach($getAllChildren as $key => $getChild){
                        $allChildrenInfo = [];
                        foreach($getChild as $key => $getAllChild){
                            $allChildrenInfo[] =  User::findAll(['id' => $getAllChild->student_id]);
                        }
                    }
                //}

                    /*to fix issue store every parentid in an array so before 
                    looping check if it exist in the array, if it does move to the next line else
                    */
                    /*trying to avoid checking for parentid that has already been checked so 
                    as to avoid repeatition*/
                    // $storeParentId[] = $getParent->parent_id;
                    // if(in_array($getParent->parent_id, $storeParentId)){

                    //     continue;
                    // }

                    //pass result gotten from query above to $getParentUserInfos[] below
                    // unset($getParentUserInfos[$key]->username);
                    // unset($getParentUserInfos[$key]->code);
                    // unset($getParentUserInfos[$key]->password_hash);
                    // unset($getParentUserInfos[$key]->password_reset_token);
                    // unset($getParentUserInfos[$key]->auth_key);
                    // unset($getParentUserInfos[$key]->class);
                    // unset($getParentUserInfos[$key]->status);
                    // unset($getParentUserInfos[$key]->subscription_expiry);
                    // unset($getParentUserInfos[$key]->subscription_plan);
                    // unset($getParentUserInfos[$key]->created_at);
                    // unset($getParentUserInfos[$key]->updated_at);
                    // unset($getParentUserInfos[$key]->verification_token);
                    // unset($getParentUserInfos[$key]->oauth_provider);
                    // unset($getParentUserInfos[$key]->token);
                    // unset($getParentUserInfos[$key]->token_expires);
                    // unset($getParentUserInfos[$key]->oauth_uid);
                    // unset($getParentUserInfos[$key]->last_accessed);


                    // unset($allChildrenInfo[$key]->username);
                    // unset($allChildrenInfo[$key]->code);
                    // unset($allChildrenInfo[$key]->password_hash);
                    // unset($allChildrenInfo[$key]->password_reset_token);
                    // unset($allChildrenInfo[$key]->auth_key);
                    // unset($allChildrenInfo[$key]->class);
                    // unset($allChildrenInfo[$key]->status);
                    // unset($allChildrenInfo[$key]->subscription_expiry);
                    // unset($allChildrenInfo[$key]->subscription_plan);
                    // unset($allChildrenInfo[$key]->created_at);
                    // unset($allChildrenInfo[$key]->updated_at);
                    // unset($allChildrenInfo[$key]->verification_token);
                    // unset($allChildrenInfo[$key]->oauth_provider);
                    // unset($allChildrenInfo[$key]->token);
                    // unset($allChildrenInfo[$key]->token_expires);
                    // unset($allChildrenInfo[$key]->oauth_uid);
                    // unset($allChildrenInfo[$key]->last_accessed);

                    $getParentUserInfos[] = 

                    [
                    
                        'children' => $allChildrenInfo
                    ];

                    
            }
            var_export($storeParentId);//exit;
            //var_export($getAllChildren);
            //var_export($allChildrenInfo);
            //var_export($getAllParents);
            //var_export($getAllParents);
            //var_dump($getParentUserInfos); exit;
            //unset($getParentUserInfos[0]->firstname);
            Yii::info('[School parent listing succesful] School ID:'.Utility::getSchoolId());
             return $getParentUserInfos;
             //return $allChildrenInfo;
            // return[
            //     'code' => 200,
            //     'message' => "School parent listing succesful",
            //     'data' => [
            //         'parent_id' => $getParentUserInfoAll->id,
            //         'parent_name' => $getParentUserInfoAll->firstname.' '.$getParentUserInfoAll->lastname,
            //         'phone' => $getParentUserInfoAll->phone,
            //         'email' => $getParentUserInfoAll->email,
            //         'image_url' => $getParentUserInfoAll->image,
            //         //'parent_relationship' => $getParentUserInfoAll->role,
            //         'children' =>[
            //             'child_name' => $getparentChildrenInfoAll->firstname.' '.$getparentChildrenInfoAll->lastname,
            //             'image_url' => $getparentChildrenInfoAll->image
            //             //TODO
            //             //also pass child/children class and parent relationship
            //         ]
            //     ]
            // ];
        }
    }
}