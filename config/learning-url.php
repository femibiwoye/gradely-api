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
//
//    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/learning/live-class'], 'extraPatterns' => [
//        'PUT start-class' => 'start-class',
//    ]],


];