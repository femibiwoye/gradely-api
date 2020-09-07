<?php

return [


    //Reports
    'GET v2/parent/student/report' => 'v2/student/profile/report', //

    //parent urls - trello Parent
    'GET v2/parent/children'=>'v2/parent/children/list',
    'PUT v2/parent/update-child-class/<child_id:\d+>' => 'v2/parent/children/update-child-class',
    'PUT v2/parent/reset-child-password' => 'v2/parent/children/reset-child-password',
    'DELETE v2/parent/child/<child_id:\d+>' => 'v2/parent/children/unlink-child',
    'POST v2/parent/child-code' => 'v2/parent/children/search-student-code',
    'POST v2/parent/connect-child' => 'v2/parent/children/connect-student-code',
    'POST v2/parent/signup-child' => 'v2/parent/children/signup-child',
    'GET v2/parent/child-subscription' => 'v2/payment/children-subscription',

    //Profile
    'PUT v2/parent/profile/update-email' => 'v2/student/profile/update-email', //
    'PUT v2/parent/profile/update-password' => 'v2/student/profile/update-password', //
    'PUT v2/parent/profile' => 'v2/student/profile/update', //


    //Homework Report
    'GET v2/parent/homework-report/<id:\d+>' => 'v2/student/homework/homework-report', //
    'GET v2/parent/completed-homework' => 'v2/student/homework/completed-homework', //
    'GET v2/parent/new-homework' => 'v2/student/homework/new-homework', //

    //Preference
    'GET v2/parent/profile/preference' => 'v2/student/preference/index', //
    'PUT v2/parent/profile/preference' => 'v2/student/preference/update', //
];
