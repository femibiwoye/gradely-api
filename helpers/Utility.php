<?php
 
namespace app\helpers;
 
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use app\models\User;
use app\models\Schools;

class Utility extends ActiveRecord
{

    public static function getUserId(){

        $getUser = User::findOne(['auth_key' => static::getBearerToken()]);
        
        return $getUser->id;
    }

    public static function getSchoolName(){

        $getSchool = Schools::findOne(['user_id' => static::getSchoolUserId()]);
        
        return $getSchool->name;
    }

    public static function getBearerToken(){

        $headers = Yii::$app->request->headers;
        $getTokenValue =  explode('Bearer', $headers->get('Authorization'));
        $removeSpacesFromToken =  trim($getTokenValue[1]," ");
        return $removeSpacesFromToken;
    }
}