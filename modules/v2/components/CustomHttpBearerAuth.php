<?php


namespace app\modules\v2\components;

use app\modules\v2\models\handler\SessionLogger;
use app\modules\v2\models\Schools;
use yii\filters\auth\HttpBearerAuth;
use Yii;

class CustomHttpBearerAuth extends HttpBearerAuth
{
    public function beforeAction($action)
    {
        parent::beforeAction($action);

        $user = Yii::$app->user->identity;
        if ($user->type == SharedConstant::TYPE_SCHOOL) {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            if (!empty($school->timezone) && in_array($school->timezone, \DateTimeZone::listIdentifiers())) {
                Yii::$app->setTimeZone($school->timezone);
            }
        }

        if ($handler = SessionLogger::find()->where(['user_id' => $user->id, 'updated_at' => null])->one()) {
            if (time() - strtotime($handler->created_at) < 1800) {
                $handler->updated_at = date('Y-m-d H:i:s');
                $handler->save();
            }else{
                $handler->delete();
            }
        } else {
            $handler = new SessionLogger();
            $handler->user_id = $user->id;
            $handler->type = $user->type;
            $handler->url = Yii::$app->request->absoluteUrl;
            $handler->created_at = date('Y-m-d H:i:s');
            $handler->save();
        }
        return true;
    }
}