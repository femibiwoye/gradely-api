<?php
return ['db' => [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=' . HOST . ';port=' . PORT . ';dbname=' . DATABASE,
    'username' => USERNAME,
    'password' => PASSWORD,
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',

],
    'main' => [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=' . HOST . ';port=' . PORT . ';dbname=' . DATABASE_NOTIFICATION,
    'username' => NOTIFICATION_USERNAME,
    'password' => NOTIFICATION_PASSWORD,
    'charset' => 'utf8',
],
    'cache' => [
        'class' => 'yii\caching\FileCache',
    ],
];

