<?php

return [
    //School Parents
    'GET v2/school/parents' => 'v2/school/parents',

    //School Types
    'GET v2/school/general/school-type' => 'v2/school/general/school-type',
    'GET v2/school/general/school-naming-format' => 'v2/school/general/school-naming-format',
    'PUT v2/school/general/update-format-type' => 'v2/school/general/update-format-type',

    //School Classes
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/school/classes'], 'extraPatterns' => [
        'GET <id:\d+>' => 'view',
        'GET group-classes' => 'group-classes',
        'POST generate' => 'generate-classes'
    ]],

    //School Profile
    'PUT v2/school/profile/update-email' => 'v2/school/profile/update-email',
    'PUT v2/school/profile/update-password' => 'v2/school/profile/update-password',
    'PUT v2/teacher/profile' => 'v2/teacher/profile/update',
    'GET v2/teacher/profile/preference' => 'v2/teacher/profile/preference',
    'PUT v2/teacher/profile/preference' => 'v2/teacher/profile/update-preference',
    'DELETE v2/teacher/profile/delete-account' => 'v2/teacher/profile/delete-account',
];