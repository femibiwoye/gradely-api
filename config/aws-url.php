<?php

return [

    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/aws'], 'extraPatterns' => [
        'GET lists' => 'list-bucket', //
        'POST add-bucket' => 'create-bucket', //
        'POST upload-file' => 'upload-file', //
        'GET verify-file' => 'verify-file', //
        'DELETE delete-file' => 'delete-file', //

    ]],

    //'GET v2/amazon/lists' => 'v2/amazon/list-bucket',//

];