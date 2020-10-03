<?php

return [

    //Live class and video conferencing
    'GET v2/update-live-class-video/<token:[a-zA-Z0-9/]+>' => 'v2/learning/live-class/update-live-class-video', //
    'PUT v2/learning/live-class/start-class' => 'v2/learning/live-class/start-class', //
//
//    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/learning/live-class'], 'extraPatterns' => [
//        'PUT start-class' => 'start-class',
//    ]],
];