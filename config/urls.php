<?php

return [
    //Authentications
    'POST v2/login' => 'v2/auth/login',
    'POST v2/signup/<type:[a-z]+>' => 'v2/auth/signup',
    'POST v2/logout' => 'v2/auth/logout',
    'POST v2/forgot-password' => 'v2/auth/forgot-password',
    'POST v2/reset-password' => 'v2/auth/reset-password',

    //Onboarding/General endpoints
    'PUT v2/general/update-boarding' => 'v2/general/update-boarding',
    'GET v2/general/boarding-status' => 'v2/general/boarding-status',
    'GET v2/general/country' => 'v2/general/country',
    'GET v2/general/state' => 'v2/general/state',
    'GET v2/general/timezone' => 'v2/general/timezone',
    'GET v2/general/term' => 'v2/general/term',

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
    'DELETE v2/teacher/profile/delete-personal' => 'v2/teacher/profile/delete-account',

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
    'DELETE v2/teacher/class/<class_id:\d+>' => 'v2/teacher/class/remove-class',

    //Feed Class
    'GET v2/upcoming' => 'v2/feed/upcoming',
    'POST v2/comment/<post_id:\d+>' => 'v2/feed/feed-comment',
    'POST v2/like/<post_id:\d+>' => 'v2/feed/feed-like',
    'POST v2/like-comment/<comment_id:\d+>' => 'v2/feed/comment-like',
    'POST v2/announcement' => 'v2/feed/create',
    'GET v2/feed' => 'v2/feed/index',
    'GET v2/feed/<class_id:\d+>' => 'v2/feed/index',

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
    'GET v2/teacher/homework/draft/<class_id:\d+>' => 'v2/teacher/homework/homework-draft',

    //Reports
    'GET v2/library/documents' => 'v2/library/index',
    'POST v2/library/video' => 'v2/library/feed-video',
    'GET v2/library/discussion' => 'v2/library/discussion',
    'GET v2/library/video' => 'v2/library/video',
    'GET v2/library/assessment' => 'v2/library/assessment',
    'GET v2/report/class' => 'v2/library/class-report',
    'GET v2/report/homework' => 'v2/report/homework-summary',

    //student profile
    'GET v2/student/parents' => 'v2/student/profile/parents',
    'GET v2/student/parent-invitations' => 'v2/student/profile/pending-parent-invitations',

    //Invites
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/invites'], 'extraPatterns' => [
        'POST school-admin' => 'school-admin',
        'POST school-teacher' => 'school-teacher',
        'GET verify' => 'verify',
    ]],
];