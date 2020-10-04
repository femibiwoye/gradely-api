<?php

return [

    //Live class and video conferencing
    'GET v2/update-live-class-video/<filename:[a-zA-Z0-9_/]+>' => 'v2/learning/live-class/update-live-class-video', //
    'PUT v2/learning/live-class/start-class' => 'v2/learning/live-class/start-class', //
    'PUT v2/learning/live-class/join-class' => 'v2/learning/live-class/join-class', //
    'PUT v2/learning/live-class/end-class' => 'v2/learning/live-class/end-class', //
//
//    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/learning/live-class'], 'extraPatterns' => [
//        'PUT start-class' => 'start-class',
//    ]],
];