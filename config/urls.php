<?php

return [

    'GET site/error' => 'site/error',

    //Authentications
    'POST v2/login' => 'v2/auth/login', //
    'POST v2/signup/<type:[a-z]+>' => 'v2/auth/signup', //
    'POST v2/logout' => 'v2/auth/logout', //
    'POST v2/forgot-password' => 'v2/auth/forgot-password', //
    'POST v2/reset-password' => 'v2/auth/reset-password', //
    'POST v2/validate' => 'v2/auth/validate-token', //
    'GET v2/verify-email' => 'v2/auth/verify-email', //

    //Onboarding/General endpoints
    'PUT v2/general/update-boarding' => 'v2/general/update-boarding', //
    'GET v2/general/boarding-status' => 'v2/general/boarding-status', //
    'GET v2/general/country' => 'v2/general/country', //
    'GET v2/general/state' => 'v2/general/state', //
    'GET v2/general/timezone' => 'v2/general/timezone', //
    'GET v2/general/term' => 'v2/general/term', //
    'DELETE v2/general/app' => 'v2/general/clear-notification', //
    'GET v2/general/app' => 'v2/general/app-notification',
    'GET v2/general/global-classes' => '/v2/general/global-classes', //
    'GET v2/general/avatar' => '/v2/general/avatar', //
    'GET v2/general/student-subscription' => '/v2/general/student-subscription', //
    'GET v2/general/curriculum' => '/v2/general/curriculum', //
    'GET v2/general/subject' => '/v2/general/subject', //
    'GET v2/general/class-status/<class_id:\d+>' => '/v2/general/class-status', //
    'GET v2/gradely-users-statistics' => 'v2/general/gradely-users-statistics', //
    'GET v2/school-auth-details/<sch:[a-z0-9-]+>' => 'v2/general/school-auth', //


    //Current User
    'GET v2/general/user' => 'v2/general/user', //

    //Services
    'GET v2/services/cloudinary' => 'v2/services/cloudinary',

    //Feed Class
    'GET v2/upcoming' => 'v2/feed/upcoming', //
    'POST v2/comment/<post_id:\d+>' => 'v2/feed/feed-comment', //
    'POST v2/like/<post_id:\d+>' => 'v2/feed/feed-like', //
    'POST v2/like-comment/<comment_id:\d+>' => 'v2/feed/comment-like', //
    'POST v2/announcement' => 'v2/feed/create', //
    'GET v2/feed' => 'v2/feed/index', //
    'GET v2/feed/<class_id:\d+>' => 'v2/feed/index', //
    //'GET v2/feed/upcoming' => 'v2/feed/upcoming',
    'DELETE v2/feed/delete-feed/<feed_id:\d+>' => 'v2/feed/delete-feed', //
    'DELETE v2/feed/delete-comment/<feed_id:\d+>/<comment_id:\d+>' => 'v2/feed/delete-comment', //

    //Reports
    'GET v2/library/summary/<class_id:\d+>' => 'v2/library/summary', //
    'GET v2/library/documents' => 'v2/library/index', //
    'POST v2/library/video' => 'v2/library/upload-video', //
    'POST v2/library/document' => 'v2/library/upload-document', //
    'GET v2/library/discussion' => 'v2/library/discussion', //
    'GET v2/library/video' => 'v2/library/video', //
    'GET v2/library/assessment' => 'v2/library/assessment', //
    'GET v2/report/class' => 'v2/library/class-report', //
    'GET v2/library/download-file/<file_id:[a-zA-Z0-9/]+>' => 'v2/library/download-file', //
    'DELETE v2/library/delete-file' => 'v2/library/delete-file', //
    'GET v2/report/homework' => 'v2/report/homework-summary', //
    'GET v2/report/remark/<type:[a-z]+>/<id:\d+>' => 'v2/report/get-remarks',
    'POST v2/report/remark/<type:[a-z]+>/<id:\d+>' => 'v2/report/create-remarks',

    'GET v2/report/class-performance' => 'v2/library/class-performance-report', //
    'GET v2/report/class-student-performance' => 'v2/library/class-student-performance-report', //
    'POST v2/report/get-class-report' => 'v2/report/get-class-report', //

    //Payment
    'POST v2/payment/verify-coupon' => 'v2/payment/verify-coupon', //
    'GET v2/payment/plan/<type:\w+>' => 'v2/payment/payment-plans', //
    'PUT v2/payment/cancel-subscription/<subscription_id:\d+>' => 'v2/payment/cancel-subscription', //
    'POST v2/payment/subscription' => 'v2/payment/subscription-payment', //
    'GET v2/payment/status/<id:\d+>' => 'v2/payment/payment-status', //
    'GET v2/parent/child-subscription' => 'v2/payment/children-subscription',// for parent
    'GET v2/parent/my-subscription' => 'v2/payment/child-subscription',// For student
    'GET v2/payment/billing-history' => 'v2/payment/billing-history',//
    'GET v2/payment/status' => 'v2/payment/subscription-status',//

    //website errors
    'POST v2/error/report-error' => 'v2/error/website-error',

    //Invites
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/invites'], 'extraPatterns' => [
        'POST school-admin' => 'school-admin', //
        'POST school-teacher' => 'school-teacher', //
        'POST school-parent' => 'school-parent', //
        'POST teacher-school' => 'teacher-school', //
        'POST parent-school' => 'parent-school', //
        'GET verify' => 'verify', //
        'PUT resend/<id:\d+>' => 'resend', //
        'DELETE remove/<id:\d+>' => 'remove', //
        'GET verified' => 'verified', //
        'DELETE remove-school-invited-user/<invite_id:\d+>' => 'delete-school-invited-user',
    ]],


    //Invites
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/commands'], 'extraPatterns' => [
        'GET update-school-calendar' => 'update-school-calendar', //
        'GET update-video-token' => 'update-video-token', //
        'GET update-file-token' => 'update-file-token', //

        'GET generate-weekly-recommendation' => 'generate-weekly-recommendation', // Generate weekly recommendation
        'GET generate-daily-recommendation' => 'generate-daily-recommendations', // Generate daily recommendation
    ]],

    'GET v2/report/student-mastery' => 'v2/report/mastery-report',
    'GET v2/report/mastery-report' => 'v2/report/topic-performance',

    'GET v2/mastery/topics' => 'v2/mastery/topics', //

    'GET v2/feature-user-logger' => 'v2/feature-user-logger', //
    'PUT v2/feature-user-logger' => 'v2/feature-user-logger/update', //
    'POST v2/user-action-logger' => 'v2/handler/user-action-logger', //

    'GET v2/test/copy-content' => 'v2/test/copy-content',

    'POST v2/teacher-academy-form/<type:\w+>' => 'v2/general/register-teacher-academy'
];