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
    'GET v2/general/country' => 'v2/general/country',
    'GET v2/general/state' => 'v2/general/state',
    'GET v2/general/timezone' => 'v2/general/timezone',

    //Current User
    'GET v2/general/user' => 'v2/general/user',

    //Services
    'GET v2/services/cloudinary' => 'v2/services/cloudinary',

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
    'GET v2/teacher/feed' => 'v2/teacher/feed/index',

    //Homework Class

    'GET v2/teacher/homework/<homework_id:\d+>' => 'v2/teacher/homework/homework',
    'GET v2/teacher/homework/class' => 'v2/teacher/homework/class-homeworks',
    'GET v2/teacher/homework/class/<class_id:\d*>' => 'v2/teacher/homework/class-homeworks',
    'GET v2/teacher/homework/subject/<class_id:\d*>' => 'v2/teacher/homework/subject',
    'DELETE v2/teacher/homework/<homework_id:\d+>' => 'v2/teacher/homework/delete-homework',
    'PUT v2/teacher/homework/extend/<homework_id:\d+>' => 'v2/teacher/homework/extend-date',
    'PUT v2/teacher/homework/<homework_id:\d+>/restart' => 'v2/teacher/homework/restart-homework',
    'POST v2/teacher/homework/<type:[a-z/]+>' => 'v2/teacher/homework/create',
    //'POST v2/teacher/homework/lesson' => 'v2/teacher/homework/create-lesson',
    'PUT v2/teacher/homework/<homework_id:\d+>' => 'v2/teacher/homework/update',

    //Reports
    'GET v2/teacher/library/documents' => 'v2/teacher/library/index',
    'POST v2/teacher/library/video' => 'v2/teacher/library/feed-video',

    //Invites
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/invites'], 'extraPatterns' => [
        'POST school-admin' => 'school-admin',
        'POST school-teacher' => 'school-teacher',
    ]],
];