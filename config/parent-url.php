<?php

return [

    //parent urls - trello Parent
    'GET v2/parent/children'=>'v2/parent/children/list',

    //Profile
    'PUT v2/parent/profile/update-email' => 'v2/student/profile/update-email',
    'PUT v2/parent/profile/update-password' => 'v2/student/profile/update-password',
    'PUT v2/parent/profile' => 'v2/student/profile/update',


    //Homework Report
    'GET v2/parent/homework/report/<id:\d+>' => 'v2/student/homework/homework-report',
    'GET v2/parent/completed-homework' => 'v2/student/homework/completed-homework',
    'GET v2/parent/new-homework' => 'v2/student/homework/new-homework',

    //Preference
    'GET v2/parent/profile/preference' => 'v2/student/preference/index',
    'PUT v2/parent/profile/preference' => 'v2/student/preference/update',
];
