<?php

return [


    //Teacher Profile
    'PUT v2/teacher/profile/update-email' => 'v2/teacher/profile/update-email', //
    'PUT v2/teacher/profile/update-password' => 'v2/teacher/profile/update-password', //
    'PUT v2/teacher/profile' => 'v2/teacher/profile/update', //
    'GET v2/teacher/profile/preference' => 'v2/teacher/profile/preference', //
    'PUT v2/teacher/profile/preference' => 'v2/teacher/profile/update-preference', //
    'DELETE v2/teacher/profile/delete-personal' => 'v2/teacher/profile/delete-account', //

    //Teacher classes
    'DELETE v2/teacher/student/remove/<student_id:\d+>/<class_id:\d+>' => 'v2/teacher/class/delete-student', //
    'GET v2/teacher/student/<id:\d+>' => 'v2/teacher/class/get-student', //
    'POST v2/teacher/student/remark/<id:\d+>' => 'v2/teacher/class/send-student-remark', //
    'GET v2/teacher/students/<class_id:\d+>' => 'v2/teacher/class/students-in-class', //
    'GET v2/teacher/search-school' => 'v2/teacher/class/search-school', //
    'GET v2/teacher/class' => 'v2/teacher/class/teacher-class', //
    'GET v2/teacher/teachers/<class_id:\d+>' => 'v2/teacher/class/class-teacher', //
    'GET v2/teacher/class/<class_id:\d+>' => 'v2/teacher/class/class-details', //
    'GET v2/teacher/class/school/<id:\d+>' => 'v2/teacher/class/school', //
    'GET v2/teacher/class/<code:[a-zA-Z0-9/]+>' => 'v2/teacher/class/view', //
    'POST v2/teacher/class/add-teacher' => 'v2/teacher/class/add-teacher', //
    'POST v2/teacher/class/add-teacher-class' => 'v2/teacher/class/add-teacher-school', //
    'POST v2/teacher/student/add-multiple' => 'v2/teacher/class/add-student', //
    'DELETE v2/teacher/class/<class_id:\d+>' => 'v2/teacher/class/remove-class', //


    //Homework Class
    'GET v2/teacher/homework/<homework_id:\d+>' => 'v2/teacher/homework/homework', //
    'GET v2/teacher/homework/class' => 'v2/teacher/homework/class-homeworks', //
    'GET v2/teacher/homework/class/<class_id:\d*>' => 'v2/teacher/homework/class-homeworks', //
    'GET v2/teacher/homework/subject/<class_id:\d*>' => 'v2/teacher/homework/subject', //
    'DELETE v2/teacher/homework/<homework_id:\d+>' => 'v2/teacher/homework/delete-homework', //
    'PUT v2/teacher/homework/extend/<homework_id:\d+>' => 'v2/teacher/homework/extend-date', //
    'PUT v2/teacher/homework/<homework_id:\d+>/restart' => 'v2/teacher/homework/restart-homework', //
    'POST v2/teacher/homework/<type:[a-z/]+>' => 'v2/teacher/homework/create', //
    'PUT v2/teacher/homework/publish/<homework_id:\d+>' => 'v2/teacher/homework/publish-homework', //


    //'POST v2/teacher/homework/lesson' => 'v2/teacher/homework/create-lesson',
    'PUT v2/teacher/homework/<homework_id:\d+>' => 'v2/teacher/homework/update', //
    'GET v2/teacher/homework/draft/<class_id:\d+>' => 'v2/teacher/homework/homework-draft', //

    //Topics
    'GET v2/teacher/class-topics' => 'v2/teacher/class/topics', //
    'GET v2/teacher/search-topic' => 'v2/teacher/class/search-topic', //

    //Questions
    'GET v2/teacher/class-questions' => 'v2/teacher/question/class-questions', //
    'GET v2/teacher/homework-questions' => 'v2/teacher/question/questions', //
    'GET v2/teacher/question' => 'v2/teacher/question/view', //
    'PUT v2/teacher/homework/homework-questions/<homework_id:\d+>' => 'v2/teacher/question/homework-questions', //
    'POST v2/teacher/question/<type:[a-z/]+>' => 'v2/teacher/question/create', //
    'DELETE v2/teacher/question' => 'v2/teacher/question/delete', //
    'PUT v2/teacher/question/<id:\d+>' => 'v2/teacher/question/update', //

    //Report error
    'POST v2/report/error-report/<type:\w+>' => 'v2/report/report-error', //

    //Teacher calender
    'GET v2/calender/teacher/<teacher_id:\d*>' => 'v2/teacher/calender/teacher-calender',//

    //Tutor Session
    'POST v2/teacher/catchup/remedial' => 'v2/teacher/catchup/create-session', //
    'POST v2/teacher/catchup/practice' => 'v2/teacher/catchup/create-practice', //
    'POST v2/teacher/catchup/video-recommendation' => 'v2/teacher/catchup/video-recommendation', //
    'GET v2/teacher/catchup/homework-summary-proctor/<student_id:\d+>/<assessment_id:\d+>' => 'v2/teacher/catchup/homework-summary-proctor', //
];