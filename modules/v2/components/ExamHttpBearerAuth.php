<?php


namespace app\modules\v2\components;

use app\modules\v2\models\Schools;
use yii\filters\auth\HttpBearerAuth;
use Yii;

class ExamHttpBearerAuth extends HttpBearerAuth
{
    public function beforeAction($action)
    {
        parent::beforeAction($action);
        $type = Yii::$app->user->identity->type;
        if ($type != SharedConstant::TYPE_PARENT && $type != SharedConstant::TYPE_STUDENT) {
            return false;
        }
        return true;
    }
}