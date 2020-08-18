<?php

namespace app\modules\v2\parent;

use Yii;
use yii\web\Response;
/**
 * Module module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\v2\parent\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        Yii::$app->user->enableSession = false;
        Yii::$app->response->format = Response::FORMAT_JSON;

    }
}
