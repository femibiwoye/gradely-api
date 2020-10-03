<?php

return [

    //Live class and video conferencing
    'GET v2/update-live-class-video/<token:[a-zA-Z0-9/]+>' => 'v2/learning/live-class/update-live-class-video',

    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/learning/live-class'], 'extraPatterns' => [
        'GET create-session' => 'create-session', //
    ]],
];