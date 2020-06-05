<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'ClFWPll1Nwe_IJPF2jPppZG520Bqq2YI',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
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
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST auth/signup' => 'auth/signup',
                'POST schools/generate-class' => 'schools/generate-class',
                'POST schools/classes' => 'schools/create-class',
                'PUT schools/classes/<id:\d+>' => 'schools/update-class',
                'DELETE schools/classes/<id:\d+>' => 'schools/delete-class',
                'GET schools/classes/<id:\d+>' => 'schools/view-class',
                'GET schools/classes' => 'schools/list-class',
                'GET schools/parents' => 'schools/list-parents',
                'POST invite' => 'invite/index',
                'GET schools/profile' => 'schools/view-user-profile',
                'PUT schools/profile' => 'schools/edit-user-profile',
                'GET schools/school' => 'schools/view-school-profile',
                'PUT schools/school' => 'schools/edit-school-profile',
                'GET schools/calendar' => 'schools/view-school-calendar',
                'PUT schools/calendar' => 'schools/edit-school-calendar',
                'GET schools/summaries' => 'schools/summaries',
                'POST schools/invite/teacher' => 'schools/invite-teacher',
                'GET schools/invite/teacher/>' => 'schools/get-all-teachers',
                'GET schools/invite/teacher/<id:\d+>' => 'schools/get-single-teachers',
                'POST schools/student/add' => 'schools/add-students',
                'GET schools/student/list-student-class/<id:\d+>' => 'schools/list-students-class',
                'GET schools/class/details/<id:\d+>' => 'schools/get-class-details',
                'PUT schools/class/student/change-class/<id:\d+>' => 'schools/change-student-class',
                'PUT schools/class/student/remove-child-class/<id:\d+>' => 'schools/remove-child-class',
                'PUT schools/settings/update-email' => 'schools/settings-update-email',
                'GET schools/settings/curriculum' => 'schools/settings-list-curriculum',
                'PUT schools/settings/curriculum' => 'schools/settings-update-curriculum',
                'POST schools/settings/new-curriculum' => 'schools/settings-request-new-curriculum',
                'GET schools/settings/subject' => 'schools/settings-list-subject',
                'PUT schools/settings/subject' => 'schools/settings-update-subject',
                'POST schools/settings/new-subject' => 'schools/settings-request-new-subject',
                'GET classes/list-teachers/<id:\d+>' => 'classes/list-teachers',
                'GET classes/detailed-teacher-profile/<id:\d+>' => 'classes/detailed-teacher-profile',
                'GET classes/homework-created-by-teacher/<id:\d+>' => 'classes/homework-created-by-teacher',
                'DELETE classes/remove-teacher-from-class/<id:\d+>' => 'classes/remove-teacher-from-class',
                //homework
                'POST adaptivity/homework>' => 'adaptivity/homework',
                'GET classes/homework-performance/<id:\d+>' => 'adaptivity/homework-performance',
                'GET classes/homework-review/<id:\d+>' => 'adaptivity/homework-review'

            ],
        ],
        'GradelyComponent' => [
            'class' => 'app\components\GradelyComponent',
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
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
