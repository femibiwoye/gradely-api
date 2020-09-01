<?php

return [

    //Parent profile
    'PUT v2/parent/profile/update-email' => 'v2/student/profile/update-email',


    //Reports
    'GET v2/parent/student/report/<child_id:\d+>' => 'v2/student/profile/report',

    //parent urls - trello Parent
    'GET v2/parent/children'=>'v2/parent/children/list',
];
