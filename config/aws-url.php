<?php

return [

    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/aws'], 'extraPatterns' => [
        'GET lists' => 'list-bucket', //
        'GET list-files' => 'list-files', //
        'POST add-bucket/<name:[a-z]+>' => 'create-bucket', //
        'POST upload-file/<folder:[a-z/-]+>' => 'upload-file', //
        'POST upload-file-with-token/<folder:[a-z/-]+>' => 'upload-file-with-token', //
        'GET verify-file' => 'verify-file', //
        'DELETE delete-file' => 'delete-file', //
        'GET file-detail' => 'file-detail', //

        'POST back-upload/<folder:[a-z0-9/-]+>' => 'back-upload', //

    ]],
    'POST demo/plugins/app/configurationjs'=>'v2/services/ckeditor'
    //'GET v2/amazon/lists' => 'v2/amazon/list-bucket',//

];