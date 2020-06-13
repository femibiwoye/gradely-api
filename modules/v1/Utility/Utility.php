<?php
 
namespace app\modules\v1\utility\Utility;
 
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use app\models\{User,Schools,Login};


class Utility extends ActiveRecord
{

    public static function getUserId(){

        $getUser = User::findOne(['auth_key' => static::getBearerToken()]);

        if(!empty($getUser)){
            return $getUser->id;
        }

        return [
            'code' => '200',
            'message' => 'could not get user'
        ];
        
    }

    public static function getSchoolName(){

        $getSchool = Schools::findOne(['user_id' => static::getSchoolUserId()]);

        if(!empty($getSchool)){
            return $getSchool->name;
        }

        return [
            'code' => '200',
            'message' => 'could not get school details'
        ];
    }

    public static function getBearerToken(){

        $headers = Yii::$app->request->headers;
        $getTokenValue =  explode('Bearer', $headers->get('Authorization'));
        $removeSpacesFromToken =  trim($getTokenValue[1]," ");
        return $removeSpacesFromToken;
    }

    public static function getLoginResponseByBearerToken(){
            $getUserInfo = User::findOne(['auth_key' => static::getBearerToken()]);
            if(!empty($getUserInfo)){
                $tokenExpires = $getUserInfo->token_expires;
                $authKey = $getUserInfo->auth_key;
                //unset fields that shouldnt be part of response returned
                unset($getUserInfo->auth_key);
                unset($getUserInfo->password_hash);
                unset($getUserInfo->password_reset_token);
                unset($getUserInfo->token);
                unset($getUserInfo->token_expires);
                Yii::info('[Login responce generated successfully');
                return[
                    'code' => 200,
                    'message' => 'Ok',
                    'data' => ['user' => $getUserInfo],
                    'expiry' => $tokenExpires,
                    'token' => $authKey
                ];
            }
            else{
                return[
                    'code' => 200,
                    'message' => 'invalid bearer token',
                ];
            }
    }

    public static function getSchoolUserId(){

        $getUser = User::findOne(['auth_key' => static::getBearerToken()]);


        if(!empty($getUser)){
            return $getUser->id;
        }
    }


    public static function getSchoolId(){

        $getSchoolId = Schools::find()->where(['user_id' => static::getUserId()])->one();

        if(!empty($getSchoolId)){
                return $getSchoolId->id;
        }

        return[
            'code' => 400,
            'message' => "Could not get school ID"
        ];
    }

    public static function getLoginResponse($model){
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

    public static function getSchoolType($schoolType, $format){
        
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
                $classes->school_id = static::getSchoolUserId();
                $classes->global_class_id = $i;
                $classes->slug = 'Year-'.$i;
                $classes->class_name =  'Year '.$i;
                $classes->abbreviation =  'y'.$i;
                $classes->class_code = static::abreviate(static::getSchoolName()).'/YEAR'.$i;
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
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = $slugPrepend.$i;
                    $classes->class_name =  $slugPrepend.$i;
                    $classes->abbreviation =  $slugPrepend.$i;
                    $classes->class_code = static::abreviate(static::getSchoolName()).'/YEAR'.$i;
                    $classes->save();
                }

                return $classes;
            }
            elseif($schoolType == 'secondary'){
                $classStart = "7"; $classEnd = "9";
                $year = 1;
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'junior-secondary-school-'.$year.'-'.Utility::getSchoolId();
                    $classes->class_name =  'Junior Secondary School-'.$year;
                    $classes->abbreviation =  'jss'.$year;
                    $classes->class_code = static::abreviate(static::getSchoolName()).'JSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;

                $year = 1;
                $classStart = "10"; $classEnd = "12";
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'senior-secondary-school-'.$year.'-'.Utility::getSchoolId();
                    $classes->class_name =  'Senior Secondary School-'.$year;
                    $classes->abbreviation =  'sss'.$year;
                    $classes->class_code = static::abreviate(static::getSchoolName()).'SSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;
            }

            elseif($schoolType == 'primary-secondary'){

                $classStart = "1"; $classEnd = "6"; $slugPrepend = "Primary";

                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = $slugPrepend.$i;
                    $classes->class_name =  $slugPrepend.$i;
                    $classes->abbreviation =  $slugPrepend.$i;
                    $classes->class_code = static::abreviate(static::getSchoolName()).'/YEAR'.$i;
                    $classes->save();
                }
                return $classes;

                $classStart = "7"; $classEnd = "9";
                $year = 1;
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'junior-secondary-school-'.$year.'-'.Utility::getSchoolId();
                    $classes->class_name =  'Junior Secondary School-'.$year;
                    $classes->abbreviation =  'jss'.$year;
                    $classes->class_code = static::abreviate(static::getSchoolName()).'JSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;

                $year = 1;
                $classStart = "10"; $classEnd = "12";
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'senior-secondary-school-'.$year.'-'.Utility::getSchoolId();
                    $classes->class_name =  'Senior Secondary School-'.$year;
                    $classes->abbreviation =  'sss'.$year;
                    $classes->class_code = static::abreviate(static::getSchoolName()).'SSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;
            }
        }
    }

    public static function abreviate($schoolSlug){

        $abbr = explode('-', $schoolSlug, 2);
        $str2 = isset($abbr[1]) ? substr($abbr[1], 0, 2) : '';
        $str1 = !empty($str2) ? substr($abbr[0], 0, 3) . $str2 : substr($abbr[0], 0, 5);
        return strtoupper($str1);
    }

    public static function checkIfStudentInSchool($studentSchoolId){

        if($studentSchoolId = Utility::getSchoolId()){
            return true;
        }
        else{
            return false;
        }
     }

    //actionViewClass
    public static function checkClassBelongsToSchool($id){

        $getClass = Classes::findOne(['school_id' => Utility::getSchoolId(),'class_id' => $id]);

        if(!empty($getClass)){

            Yii::info('[Class' .$id .' belongs to school] school_id:'.Utility::getSchoolId().'');
            return true;
        }

        return false;
    }
    
}