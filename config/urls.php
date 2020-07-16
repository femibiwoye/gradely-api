<?php

return [
    //Authentications
    'POST v2/login' => 'v2/auth/login',
    'POST v2/signup/<type:[a-z]+>' => 'v2/auth/signup',
    'POST v2/logout' => 'v2/auth/logout',
    'POST v2/forgot-password' => 'v2/auth/forgot-password',
    'POST v2/reset-password' => 'v2/auth/reset-password',

    //Onboarding
    'PUT v2/general/update-boarding' => 'v2/general/update-boarding',
    'GET v2/general/boarding-status' => 'v2/general/boarding-status',

    //Teacher Profile
    'PUT v2/teacher/profile/update-email' => 'v2/teacher/profile/update-email',
    'PUT v2/teacher/profile/update-password' => 'v2/teacher/profile/update-password', 
    'PUT v2/teacher/profile' => 'v2/teacher/profile/update',
    'GET v2/teacher/profile/preference' => 'v2/teacher/profile/preference',
    'PUT v2/teacher/profile/preference' => 'v2/teacher/profile/update-preference',
    'DELETE v2/teacher/profile/delete-account' => 'v2/teacher/profile/delete-account',

    //Teacher classes
    'DELETE v2/teacher/student/remove/<student_id:\d+>/<class_id:\d+>' => 'v2/teacher/class/delete-student',
    'GET v2/teacher/student/<id:\d+>' => 'v2/teacher/class/get-student',
    'GET v2/teacher/students/<class_id:\d+>' => 'v2/teacher/class/students-in-class',
    'GET v2/teacher/search-school' => 'v2/teacher/class/search-school',
    'GET v2/teacher/class' => 'v2/teacher/class/teacher-class',
    'GET v2/teacher/class/school/<id:\d+>' => 'v2/teacher/class/school',
    'GET v2/teacher/class/<code:[a-zA-Z0-9/]+>' => 'v2/teacher/class/view',
    'POST v2/teacher/class/add-teacher' => 'v2/teacher/class/add-teacher',
    'POST v2/teacher/class/add-teacher-class' => 'v2/teacher/class/add-teacher-school',
    'POST v2/teacher/student/add-multiple' => 'v2/teacher/class/add-student',

    //Feed Class
    'GET v2/teacher/upcoming' => 'v2/teacher/feed/upcoming',
    'POST v2/teacher/comment/<post_id:\d+>' => 'v2/teacher/feed/feed-comment',
    'POST v2/teacher/like/<post_id:\d+>' => 'v2/teacher/feed/feed-like',
    'POST v2/teacher/like-comment/<comment_id:\d+>' => 'v2/teacher/feed/comment-like',
    'POST v2/teacher/announcement' => 'v2/teacher/feed/create',

    //School Parents
    'GET v2/school/parents' => 'v2/school/parents',

    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/school/classes'],'extraPatterns' => [
        'GET <id:\d+>' => 'view',
    ]],

    ['class' => 'yii\rest\UrlRule', 'controller' => ['module\v2\signup'], 'extraPatterns' => [
        'POST create' => 'create',
        'OPTIONS signup' => 'options',
    ]],
];