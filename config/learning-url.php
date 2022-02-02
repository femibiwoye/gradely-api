<?php

return [
    'POST v2/live-class' => 'v2/feed/new-live-class', //
    'PUT v2/live-class/reschedule/<id:\d+>' => 'v2/learning/live-class/reschedule', //
    'PUT v2/live-class/update/<id:\d+>' => 'v2/learning/live-class/update', //
    'DELETE v2/live-class/<id:\d+>' => 'v2/learning/live-class/delete', //

    //Live class and video conferencing
    'GET v2/update-live-class-video/<filename:[a-zA-Z0-9-._/]+>' => 'v2/learning/live-class/update-live-class-video', //
    'PUT v2/learning/live-class/start-class' => 'v2/learning/live-class/start-class', //
    'PUT v2/learning/live-class/join-class' => 'v2/learning/live-class/join-class', //
    'PUT v2/learning/live-class/end-class' => 'v2/learning/live-class/end-class', //
    'GET v2/learning/live-class/end-class-only' => 'v2/learning/live-class/end-class-only', //
    'POST v2/learning/live-class/log-recording' => 'v2/learning/live-class/log-recording', //
    'GET v2/learning/live-class/check-class-status' => 'v2/learning/live-class/check-class-status', //
//
//    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/learning/live-class'], 'extraPatterns' => [
//        'PUT start-class' => 'start-class',
//    ]],

//For Big Blue Button
    'GET v2/learning/bbb' => 'v2/learning/big-blue-button/start',
    'GET v2/learning/live-class/public-class' => 'v2/learning/live-class/public-class', //
    'GET v2/learning/live-class/start-class' => 'v2/learning/public-class/start-class', //
    'GET v2/learning/live-class/create-class' => 'v2/learning/public-class/create-class', //

];