<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\Schools;
use app\models\Classes;
use app\models\GlobalClass;
use app\models\ContactForm;
use app\models\User;
use app\models\StudentSchool;
use app\models\Parents;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\ContentNegotiator;


/**
 * Schools controller
 */
//class SiteController extends Controller
class SchoolsController extends ActiveController
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

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['generate-class'],
                'only' => ['create-class'],
                'only' => ['view-class'],
                'only' => ['delete-class'],
                'only' => ['list-parents'],
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


    public function actionGenerateClass(){

    $request = \yii::$app->request->post();
    //$this->getSchoolType('primary','grade 1-12');
    //$this->getSchoolType('secondary','grade 1-12');
    //$this->getSchoolType('primary','year 1-12');
    //$this->getSchoolType('secondary','year 1-12');
    //$this->getSchoolType('primary-secondary','year 1-12');
    $generateSchool = $this->getSchoolType($request['school-type'],$request['format']);

    if($generateSchool){
        return[
            'code' => 200,
            'message' => "Successfully created"
        ];
    }
}

    public function actionCreateClass(){

        $classes = new Classes();
        $classes->school_id = $request['school_id'];
        $classes->global_class_id = $request['global_class_id'];
        $classes->class_name = $request['class_name'];
        $classes->class_code = $request['class_code'];
        $classes->slug = \yii\helpers\Inflector::slug($request['class_name']);
        $classes->abbreviation = $this->abreviate($classes->slug);

        if ($classes->save()) {
            return[
                'code' => 200,
                'message' => "Successfully created"
            ];
        }

        $classes->validate();
        return $classes;
    }

    public function actionListClass(){


        //create a method that gets the users bearer token from the header
        // then using the bearer token, get the userid, then use the userid to get to get to get the schoolid
        //then use the schooid to list
        echo'this is for listing classes';
        $getAllClasses = classes::find(['school_id' => $this->getSchoolUserId()])->all();

        if(!empty($getAllClasses)){

            return[
                'code' => 200,
                'message' => "Succesfull",
                'date'=> $getAllClasses
            ];
        }

        return[
            'code' => 200,
            'message' => "Couldnt find any class for this school"
        ];
    }

    public function actionViewClass($id){

        echo'this is for viewing classes'.$id.'';

        $getClass = Classes::findOne(['school_id' => $id]);

        if(!empty($getClass)){

            return[
                'code' => 200,
                'message' => "successful",
                'data' => $getClass
            ];
        }

        return[
            'code' => 200,
            'message' => "Couldnt find any class with this id"
        ];
    }

    public function actionUpdateClass($id){
        
        $request = \yii::$app->request->post();

        $getClass = Classes::findOne(['id' => $id]);
        //$getClass = Classes::find()->where(['id' => $id])->one();
        if(!empty($getClass)){
            
            $getClass->global_class_id = $request['global_class_id'];
            $getClass->class_name = $request['class_name'];
            $getClass->abbreviation = $request['class_code'];

            try{
                
                $getClass->save();
                return[
                    'code' => 200,
                    'message' => "update succesful"
                ];
            }
            catch (Exception $exception){
                return[
                    'code' => 200,
                    'message' => $exception->getMessage()
                ];
            }
        }

        return[
            'code' => 200,
            'message' => 'class does not exist'
        ];
    }

    public function actionDeleteClass($id){

        $getClass = Classes::findOne(['id' => $id]);

        if(!empty($getClass)){

            try{
                $getClass->delete();
                return[
                    'code' => 200,
                    'message' => "delete succesful"
                ];
            }
            catch (Exception $exception){
                return[
                    'code' => 200,
                    'message' => $exception->getMessage()
                ];
            }
        }

        return[
            'code' => 200,
            'message' => 'class does not exist'
        ];
    }

    public function getBearerToken(){

        $headers = Yii::$app->request->headers;
        $getTokenValue =  explode('Bearer', $headers->get('Authorization'));
        $removeSpacesFromToken =  trim($getTokenValue[1]," ");
        return $removeSpacesFromToken;
    }

    public function getSchoolUserId(){

        $getUser = User::findOne(['auth_key' => $this->getBearerToken()]);
        
        return $getUser->id;
    }

    public function getSchoolName(){

        $getSchool = Schools::findOne(['user_id' => $this->getSchoolUserId()]);
        
        return $getSchool->name;
    }

    public function abreviate($schoolSlug){

        $abbr = explode('-', $schoolSlug, 2);
        $str2 = isset($abbr[1]) ? substr($abbr[1], 0, 2) : '';
        $str1 = !empty($str2) ? substr($abbr[0], 0, 3) . $str2 : substr($abbr[0], 0, 5);
        return strtoupper($str1);
    }

    public function getSchoolType($schoolType, $format){
        
        if($format == 'grade 1-12'){
            $classStart = ""; $classEnd = "";
            if($schoolType == 'primary'){   
                $classStart = "1"; $classEnd = "6";
            }
            elseif($schoolType == 'secondary'){
                $classStart = 7; $classEnd = 12;
            }
            elseif($schoolType == 'primary-secondary'){
                $classStart = "1"; $classEnd = "12";
            }
            //$i= 1;
            for($i = $classStart; $i <= $classEnd; $i++){
                $classes = new Classes();
                $classes->school_id = $this->getSchoolUserId();
                $classes->global_class_id = $i;
                $classes->slug = 'Year-'.$i;
                $classes->class_name =  'Year '.$i;
                $classes->abbreviation =  'y'.$i;
                $classes->class_code = $this->abreviate($this->getSchoolName()).'/YEAR'.$i;
                $classes->save();
            }
            return $classes;
        }



        if($format == 'year 1-12'){
        
            $classStart = ""; $classEnd = ""; $slugPrepend = "";
            if($schoolType == 'primary'){   

                $classStart = "1"; $classEnd = "6"; $slugPrepend = "Primary";

                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = $this->getSchoolUserId();
                    $classes->global_class_id = $i;
                    $classes->slug = $slugPrepend.$i;
                    $classes->class_name =  $slugPrepend.$i;
                    $classes->abbreviation =  $slugPrepend.$i;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'/YEAR'.$i;
                    $classes->save();
                }

                return $classes;
            }
            elseif($schoolType == 'secondary'){
                $classStart = "7"; $classEnd = "9";
                $year = 1;
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = $this->getSchoolUserId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'junior-secondary-school-'.$year.'-'.$this->getSchoolUserId();
                    $classes->class_name =  'Junior Secondary School-'.$year;
                    $classes->abbreviation =  'jss'.$year;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'JSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;

                $year = 1;
                $classStart = "10"; $classEnd = "12";
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = $this->getSchoolUserId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'senior-secondary-school-'.$year.'-'.$this->getSchoolUserId();
                    $classes->class_name =  'Senior Secondary School-'.$year;
                    $classes->abbreviation =  'sss'.$year;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'SSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;
            }

            elseif($schoolType == 'primary-secondary'){

                $classStart = "1"; $classEnd = "6"; $slugPrepend = "Primary";

                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = $this->getSchoolUserId();
                    $classes->global_class_id = $i;
                    $classes->slug = $slugPrepend.$i;
                    $classes->class_name =  $slugPrepend.$i;
                    $classes->abbreviation =  $slugPrepend.$i;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'/YEAR'.$i;
                    $classes->save();
                }
                return $classes;

                $classStart = "7"; $classEnd = "9";
                $year = 1;
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = $this->getSchoolUserId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'junior-secondary-school-'.$year.'-'.$this->getSchoolUserId();
                    $classes->class_name =  'Junior Secondary School-'.$year;
                    $classes->abbreviation =  'jss'.$year;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'JSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;

                $year = 1;
                $classStart = "10"; $classEnd = "12";
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = $this->getSchoolUserId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'senior-secondary-school-'.$year.'-'.$this->getSchoolUserId();
                    $classes->class_name =  'Senior Secondary School-'.$year;
                    $classes->abbreviation =  'sss'.$year;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'SSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;
            }
        }
    }

    public function getSchoolId(){

        $getSchoolUserId = Schools::find()->where(['user_id' => $this->getSchoolUserId()])->one();

        if(!empty($getSchoolUserId)){
                return $getSchoolUserId->id;
        }

        return[
            'code' => 400,
            'message' => "Could not get school ID"
        ];
    }

    public function actionListParents(){

        //get all students 
        $getAllStudents = StudentSchool::find()->where(['school_id' => $this->getSchoolId()])->one();

        //get parents
        $getAllParents = Parents::find()->where(['student_id' => $getAllStudents->student_id])->all();

        $Parentchildren = [];
        foreach($getAllParents as $getParent){

            //get children id from parent
            $Parentchildren = $getParent->student_id;

            $getParentchildrenInfos = User::find()->where(['id' => $getParent->student_id])->all();
            //get parent info
            $getParentUserInfos = User::find()->where(['id' => $getParent->parent_id])->all();
        }

        //parentinfo
        foreach($getParentUserInfos as $getParentUserInfo){

            $getParentUserInfoAll = $getParentUserInfo;
        }

        //childreninfo
        foreach($getParentchildrenInfos as $getParentchildrenInfo){

            $getParentchildrenInfoAll = $getParentchildrenInfo;
        }

        return[
            'code' => 200,
            'message' => "Successful",
            'data' => [
                'parent_id' => $getParentUserInfoAll->id,
                'parent_name' => $getParentUserInfoAll->firstname.' '.$getParentUserInfoAll->lastname,
                'phone' => $getParentUserInfoAll->phone,
                'email' => $getParentUserInfoAll->email,
                'image_url' => $getParentUserInfoAll->image,
                'children' =>[
                    'child_id' => $getParentchildrenInfoAll->id,
                    'image_url' => $getParentchildrenInfoAll->image
                    //TODO
                    //also pass child class and parent relationship
                ]
            ]
        ];
    }

}