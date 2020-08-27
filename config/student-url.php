<?php

return [

    //student profile
    'GET v2/student/parents' => 'v2/student/profile/parents',
    'GET v2/student/parent-invitations' => 'v2/student/profile/pending-parent-invitations',
    'PUT v2/student/profile/update-email' => 'v2/student/profile/update-email',
    'PUT v2/student/profile/update-password' => 'v2/student/profile/update-password',
    'PUT v2/student/profile' => 'v2/student/profile/update',
    'PUT v2/student/avatar' => 'v2/student/profile/update-avatar',

    //student general
    'GET v2/student/security-questions' => 'v2/student/general/security-questions',
    'PUT v2/student/security-question' => 'v2/student/general/set-security-question',

    //student homeworks
    'GET v2/student/completed-homework' => 'v2/student/homework/completed-homework',
    'GET v2/student/new-homework' => 'v2/student/homework/new-homework',
    'GET v2/student/homework/score/<homework_id:\d+>' => 'v2/student/homework/homework-score',
    'GET v2/student/homework/report/<id:\d+>' => 'v2/student/homework/homework-report',

    //Student Practice
    'GET v2/student/practice/homework-instruction/<homework_id:\d+>' => 'v2/student/practice/homework-instruction',

    //student class
    'GET v2/student/verify-class/<code:[a-zA-Z0-9/]+>' => 'v2/student/class/verify-class',
    'POST v2/student/class' => 'v2/student/class/student-class',
    'GET v2/student/class' => 'v2/student/class/student-class-details',

    //student preference
    'GET v2/student/profile/preference' => 'v2/student/preference/index',
    'PUT v2/student/profile/preference' => 'v2/student/preference/update',

    //student report
    'GET v2/student/report' => 'v2/student/profile/report',

    //student invites
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/invites'], 'extraPatterns' => [
        'POST student-parent' => 'student-parent'
    ]],
];