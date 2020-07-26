<?php


namespace app\modules\v2\components;

use app\modules\v2\models\Schools;
use yii\filters\auth\HttpBearerAuth;
use Yii;

class CustomHttpBearerAuth extends HttpBearerAuth
{
    public function beforeAction($action)
    {
        parent::beforeAction($action);

        if (Yii::$app->user->identity->type == SharedConstant::TYPE_SCHOOL) {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            if (!empty($school->timezone) && in_array($school->timezone, \DateTimeZone::listIdentifiers())) {
                Yii::$app->setTimeZone($school->timezone);
            }
        }

        return true;
    }
}