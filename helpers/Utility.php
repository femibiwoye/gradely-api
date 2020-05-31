<?php
 
namespace app\helpers;
 
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
}