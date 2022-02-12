<?php

use yii\base\Event;

require 'var.php';
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$mainUrl = require 'urls.php';
$schoolUrl = require 'school-url.php';
$studentUrl = require 'student-url.php';
$teacherUrl = require 'teacher-url.php';
$parentUrl = require 'parent-url.php';
$tutorUrl = require 'tutor-url.php';
$learningUrl = require 'learning-url.php';
$awsUrl = require 'aws-url.php';
$commandUrl = require 'command-url.php';
$testUrl = require 'test-url.php';
$smsUrl = require 'sms-url.php';
$examUrl = require 'exam-url.php';
$gameUrl = require 'game-url.php';
$summerUrl = require 'summer-url.php';

$config = [
    'id' => 'gradely-v2',
    'name' => 'gradely-v2',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'timeZone' => 'Africa/Lagos',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'modules' => [
        'v2' => [
            'class' => 'app\modules\v2\Module',
        ],
    ],
    'components' => [
        'response' => [
            'format' => \yii\web\Response::FORMAT_JSON,
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->statusCode > 401 && is_array($response->data)) {
                    //Save the error to db so that it will be logged

                    \app\modules\v2\components\LogError::widget(['source' => 'API', 'name' => $response->data['name'], 'raw' => json_encode($response->data)]);
                } else {
                    \app\modules\v2\components\RequestLogger::widget(['method' => Yii::$app->request->method, 'code' => $response->statusCode, 'request' => Yii::$app->request->bodyParams, 'response' => json_encode($response->data)]);
                }
            },
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'enableCookieValidation' => false,
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\modules\v2\models\User',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,

        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                $params['applicationStage'] == 'local' ? [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ] : [
                    'class' => 'notamedia\sentry\SentryTarget',
                    'dsn' => $params['sentryDNS'],
                    'levels' => ['error', 'warning'],
                    // Write the context information (the default is true):
                    'context' => true,
                    // Additional options for `Sentry\init`:
                    'clientOptions' => ['release' => 'gradely-php']
                ],
            ],
        ],

        'db' => $db['db'],
        'dblive' => $db['dblive'],
        'db_test' => $db['db_test'],
        'notification' => $db['main'],
        'sms' => $db['sms'],
        'game' => $db['game'],
        'handler' => $db['handler'],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => array_merge($mainUrl, $schoolUrl, $teacherUrl, $studentUrl, $parentUrl, $learningUrl, $tutorUrl, $awsUrl, $commandUrl, $testUrl, $smsUrl, $examUrl, $gameUrl, $summerUrl),
        ],

    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1', '192.168.*.*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1', '192.168.*.*', '172.24.0.1', '*'],
    ];
}

return $config;
