<?php

return [
    //School Parents
    'GET v2/school/parents' => 'v2/school/parents',

    //School General
    'GET v2/school/general/school-type' => 'v2/school/general/school-type',
    'GET v2/school/general/school-naming-format' => 'v2/school/general/school-naming-format',
    'GET v2/school/general/school-roles' => 'v2/school/general/school-roles',
    'PUT v2/school/general/update-format-type' => 'v2/school/general/update-format-type',

    //School Classes
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/school/classes'], 'extraPatterns' => [
        'GET <id:\d+>' => 'view',
        'GET group-classes' => 'group-classes',
        'POST generate' => 'generate-classes',
        'PUT update' => 'update'
    ]],

    //School Profile
    'PUT v2/school/profile/update-email' => 'v2/school/profile/update-email',
    'PUT v2/school/profile/update-password' => 'v2/school/profile/update-password',
    'PUT v2/school/profile' => 'v2/school/profile/update',
    'GET v2/school/profile/preference' => 'v2/school/school/preference',
    'PUT v2/school/profile/preference' => 'v2/school/profile/update-preference',
    'PUT v2/school/profile/update-school' => 'v2/school/profile/update-school',
    'DELETE v2/school/profile/delete-personal' => 'v2/school/profile/delete-personal',
    'DELETE v2/school/profile/delete-school' => 'v2/school/profile/delete-school',

    //School Preferences
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/school/preferences'], 'extraPatterns' => [
        'GET curriculum' => 'curriculum',
        'POST new-curriculum' => 'new-curriculum',
        'PUT update-curriculum' => 'update-curriculum',
        'GET subjects' => 'subjects',
        'POST add-subject' => 'add-subject',
    ]],
];