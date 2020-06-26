<?php

namespace app\modules\v2;

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
    public $controllerNamespace = 'app\modules\v2\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        \Yii::$app->user->enableSession = false;
        Yii::$app->response->format = Response::FORMAT_JSON;
        /*$this->modules = [
            'school' => [
                'class' => 'app\modules\v2\school\Module',
            ],
            'invite' => [
                'class' => 'app\modules\v2\invite\Module',
            ]
        ];*/

    }

}
