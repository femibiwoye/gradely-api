<?php


namespace app\modules\v2\components;

use app\modules\v2\models\WebsiteError;
use yii\base\Widget;
use Yii;

class RequestLogger extends Widget
{
    public $method;
    public $response;
    public $request;
    public $code;

    public function run()
    {


        $user = Yii::$app->user->isGuest ? 0 : Yii::$app->user->id;

        $currentUrl = Yii::$app->request->absoluteUrl;

        $err = new \app\modules\v2\models\handler\RequestLogger();
        $err->method = $this->method;
        $err->request = ($this->request);
        $err->response = ($this->response);
        $err->url = $currentUrl;
        $err->code = (string) $this->code;
        $err->user_id = $user;
        $err->save();
    }
}