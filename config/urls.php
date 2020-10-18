<?php

return [
    //Authentications
    'POST v2/login' => 'v2/auth/login', //
    'POST v2/signup/<type:[a-z]+>' => 'v2/auth/signup', //
    'POST v2/logout' => 'v2/auth/logout', //
    'POST v2/forgot-password' => 'v2/auth/forgot-password', //
    'POST v2/reset-password' => 'v2/auth/reset-password', //
    'POST v2/validate' => 'v2/auth/validate-token', //

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

    'POST v2/live-class' => 'v2/feed/new-live-class', //
    'PUT v2/live-class-availability/<id:\d+>' => 'v2/feed/update-live-class-availability', //
    'PUT v2/live-class-details/<id:\d+>' => 'v2/feed/update-live-class-details', //
    'DELETE v2/live-class/<id:\d+>' => 'v2/feed/delete-live-class', //

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
    'GET v2/report/student-mastery' => 'v2/report/mastery-report',


    //Payment
    'POST v2/payment/verify-coupon' => 'v2/payment/verify-coupon', //
    'GET v2/payment/plan/<type:\w+>' => 'v2/payment/payment-plans', //
    'PUT v2/payment/cancel-subscription/<subscription_id:\d+>' => 'v2/payment/cancel-subscription', //
    'POST v2/payment/subscription' => 'v2/payment/subscription-payment', //
    'GET v2/payment/status/<id:\d+>' => 'v2/payment/payment-status', //
    'GET v2/parent/child-subscription' => 'v2/payment/children-subscription',// for parent
    'GET v2/parent/my-subscription' => 'v2/payment/child-subscription',// For student
    'GET v2/payment/billing-history' => 'v2/payment/billing-history',//

    //website errors
    'POST v2/error/report-error' => 'v2/error/website-error',

    //Invites
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/invites'], 'extraPatterns' => [
        'POST school-admin' => 'school-admin', //
        'POST school-teacher' => 'school-teacher', //
        'POST teacher-school' => 'teacher-school', //
        'GET verify' => 'verify', //
        'PUT resend/<id:\d+>' => 'resend', //
        'DELETE remove/<id:\d+>' => 'remove', //
        'PUT verified' => 'verified', //
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

];