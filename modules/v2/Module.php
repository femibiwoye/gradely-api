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
//        \Sentry\init(['dsn' => '____DSN____' ]);
        \Yii::$app->user->enableSession = false;
        Yii::$app->request->enableCsrfValidation = false;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->modules = [
            'school' => [
                'class' => 'app\modules\v2\school\Module',
            ],
            'teacher' => [
                'class' => 'app\modules\v2\teacher\Module',
            ],
            'student' => [
                'class' => 'app\modules\v2\student\Module',
            ],
            'parent' => [
                'class' => 'app\modules\v2\parent\Module',
            ],
            'tutor' => [
                'class' => 'app\modules\v2\tutor\Module',
            ],
            'learning' => [
                'class' => 'app\modules\v2\learning\Module',
            ],
            'sms' => [
                'class' => 'app\modules\v2\sms\Module',
            ],
            'exam' => [
                'class' => 'app\modules\v2\exam\Module',
            ]
        ];

    }

}
