<?php

return [
    'POST v2/login' => 'v2/auth/login',
    'POST v2/signup/<type:[a-z]+>' => 'v2/auth/signup',
    'POST v2/logout' => 'v2/auth/logout',
    'POST v2/forgot-password' => 'v2/auth/forgot-password',
    'POST v2/reset-password' => 'v2/auth/reset-password',


    'PUT v2/general/update-boarding' => 'v2/general/update-boarding',
    'GET v2/general/boarding-status' => 'v2/general/boarding-status',


    'PUT v2/teacher/profile/update-email' => 'v2/teacher/profile/update-email',
    'PUT v2/teacher/profile/update-password' => 'v2/teacher/profile/update-password', 
    'PUT v2/teacher/profile' => 'v2/teacher/profile/update',
    'PUT v2/teacher/profile/preference' => 'v2/teacher/profile/preference',
    'DELETE v2/teacher/profile/delete-account' => 'v2/teacher/profile/delete-account',

    'GET v2/teacher/students/<class_id:[1-9]+>' => 'v2/teacher/class/students-in-class',
    'GET v2/teacher/search-school' => 'v2/teacher/class/search-school',
    'GET v2/teacher/class' => 'v2/teacher/class/teacher-class',
    'GET v2/teacher/class/school/<id:[0-9]+>' => 'v2/teacher/class/school',
    'GET v2/teacher/class/<code:[a-zA-Z0-9/]+>' => 'v2/teacher/class/view',
    'POST v2/teacher/class/add-teacher' => 'v2/teacher/class/add-teacher',
    'POST v2/teacher/class/add-teacher-class' => 'v2/teacher/class/add-teacher-school',


    ['class' => 'yii\rest\UrlRule', 'controller' => ['modules\v2\auth'], 'extraPatterns' => [
        'POST login' => 'login',
        'OPTIONS login' => 'login',
        'POST reset-password' => 'reset-password',
        'OPTIONS reset-password' => 'reset-password',
        'POST forgot-password' => 'forgot-password',
        'OPTIONS forgot-password' => 'forgot-password',
        'GET logout' => 'logout',
        'OPTIONS logout' => 'options',
    ]],
    ['class' => 'yii\rest\UrlRule', 'controller' => ['module\v2\signup'], 'extraPatterns' => [
        'POST create' => 'create',
        'OPTIONS signup' => 'options',
    ]],
];