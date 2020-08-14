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

    //student homeworks
    'GET v2/student/completed-homeworks' => 'v2/student/homework/completed-homeworks',

];