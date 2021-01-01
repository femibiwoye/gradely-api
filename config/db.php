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
    'on afterOpen' => function ($event) {
        $event->sender->createCommand("SET time_zone='+01:00';")->execute();
    },

], 'db_test' => [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=' . HOST . ';port=' . PORT . ';dbname=' . DATABASE_TEST,
    'username' => USERNAME,
    'password' => PASSWORD,
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
    'on afterOpen' => function ($event) {
        $event->sender->createCommand("SET time_zone='+01:00';")->execute();
    },
],
    'main' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host=' . HOST . ';port=' . PORT . ';dbname=' . DATABASE_NOTIFICATION,
        'username' => NOTIFICATION_USERNAME,
        'password' => NOTIFICATION_PASSWORD,
        'charset' => 'utf8',
        'on afterOpen' => function ($event) {
            $event->sender->createCommand("SET time_zone='+01:00';")->execute();
        },
    ],
    'cache' => [
        'class' => 'yii\caching\FileCache',
    ],
];

