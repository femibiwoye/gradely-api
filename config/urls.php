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


    //Feed Class
    'GET v2/upcoming' => 'v2/feed/upcoming',
    'POST v2/comment/<post_id:\d+>' => 'v2/feed/feed-comment',
    'POST v2/like/<post_id:\d+>' => 'v2/feed/feed-like',
    'POST v2/like-comment/<comment_id:\d+>' => 'v2/feed/comment-like',
    'POST v2/announcement' => 'v2/feed/create',
    'GET v2/feed' => 'v2/feed/index',
    'GET v2/feed/<class_id:\d+>' => 'v2/feed/index',

    //Reports
    'GET v2/library/documents' => 'v2/library/index',
    'POST v2/library/video' => 'v2/library/feed-video',
    'GET v2/library/discussion' => 'v2/library/discussion',
    'GET v2/library/video' => 'v2/library/video',
    'GET v2/library/assessment' => 'v2/library/assessment',
    'GET v2/report/class' => 'v2/library/class-report',
    'GET v2/report/homework' => 'v2/report/homework-summary',

    //Payment
    'POST v2/payment/verify-coupon' => 'v2/payment/verify-coupon',
    'GET v2/payment/plan/<type:\w+>' => 'v2/payment/payment-plans',

    //Invites
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/invites'], 'extraPatterns' => [
        'POST school-admin' => 'school-admin',
        'POST school-teacher' => 'school-teacher',
        'POST teacher-school' => 'teacher-school',
        'GET verify' => 'verify',
    ]],
];