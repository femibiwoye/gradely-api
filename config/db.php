<?php
require 'var.php';
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host='.HOST.';port='.PORT.';dbname='.DATABASE,
    'username' => USERNAME,
    'password' => PASSWORD,
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];

