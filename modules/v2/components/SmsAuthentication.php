<?php

namespace app\modules\v2\components;

use yii\db\ActiveRecord;
use Yii;
use app\modules\v2\sms\models\Schools;

class SmsAuthentication extends ActiveRecord
{
    public static function checkStatus()
    {
        return Schools::find()->where(['school_key' => Yii::$app->request->get('school_key'), 'school_secret' => Yii::$app->request->get('school_secret_key'), 'status' => 1, 'approved' => 1])->exists();
    }

    public static function getSchool($idOnly = 1)
    {
        $school = Schools::find()->where(['school_key' => Yii::$app->request->get('school_key'), 'school_secret' => Yii::$app->request->get('school_secret_key'), 'status' => 1, 'approved' => 1])->one();
        if ($idOnly == 1) {
            return $school->school_id;
        }
        return $school;
    }
}