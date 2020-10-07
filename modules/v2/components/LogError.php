<?php


namespace app\modules\v2\components;

use app\modules\v2\models\WebsiteError;
use yii\base\Widget;
use Yii;

class LogError extends Widget
{
    public $name;
    public $source;
    public $raw;

    public function run()
    {

        if (!isset(Yii::$app->request->referrer)) {
            $prev = null;
        } else {
            $prev = Yii::$app->request->referrer;
        }
        if (Yii::$app->user->isGuest) {
            $user = 'Visitor';
        } else {
            $user = Yii::$app->user->id;
        }

        $currentUrl = Yii::$app->request->absoluteUrl;
        if (!WebsiteError::find()->where(['error' => $this->name, 'current' => $currentUrl, 'user' => $user])->exists()) {
            $err = new WebsiteError();
            $err->error = $this->name;
            $err->user = $user;
            $err->current = $currentUrl;
            $err->previous = $prev;
            $err->raw = $this->raw;
            $err->source = $this->source;
            $err->save();
        }
    }
}