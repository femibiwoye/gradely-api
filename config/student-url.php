<?php

return [

    //student profile
    'GET v2/student/parents' => 'v2/student/profile/parents',
    'GET v2/student/parent-invitations' => 'v2/student/profile/pending-parent-invitations',
    'PUT v2/student/update-email' => 'v2/student/profile/update-email',
    'PUT v2/student/update-password' => 'v2/student/profile/update-password',
    'PUT v2/student' => 'v2/student/profile/update',

    //student general
    'GET v2/student/questions' => 'v2/student/general/security-questions',
    'POST v2/student/question' => 'v2/student/general/set-security-question',
    'PUT v2/student/question/<id:\d+>' => 'v2/student/general/update-security-question',

    //student homeworks
    'GET v2/student/completed-homeworks' => 'v2/student/homework/completed-homeworks',
    'GET v2/student/new-homeworks' => 'v2/student/homework/new-homeworks',

    //student class
    'POST v2/student/class' => 'v2/student/class/student-class',

    //student preference
    'GET v2/student/preference' => 'v2/student/preference/index',

    //student invites
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/invites'], 'extraPatterns' => [
        'POST student-parent' => 'student-parent',
    ]],
];