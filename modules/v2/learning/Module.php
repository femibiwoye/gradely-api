<?php

namespace app\modules\v2\learning;

use yii\web\Response;
use Yii;

/**
 * learning module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\v2\learning\controllers';

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
