<?php

return [

    //Tutor
    'POST v2/sms/class/map-class-subject' => 'v2/sms/class/map-class-subject',
    'GET v2/sms/class/get-class-subjects' => 'v2/sms/class/get-class-subjects',

    'POST v2/sms/class/create-class-arm' => 'v2/sms/class/create-class-arm',
    'PUT v2/sms/class/update-class-arm' => 'v2/sms/class/update-class-arm',
    'GET v2/sms/class/get-global-class-arms' => 'v2/sms/class/get-global-class-arms',


    'POST v2/sms/teacher/assign-subject-to-teacher' => 'v2/sms/teacher/assign-subject-to-teacher', //
    'POST v2/sms/teacher/assign-subjects-to-teacher' => 'v2/sms/teacher/assign-subjects-to-teacher', //
    'DELETE v2/sms/teacher/remove-teacher-from-class' => 'v2/sms/teacher/remove-class-teacher', //
    'DELETE v2/sms/teacher/remove-teacher-from-school' => 'v2/sms/teacher/remove-teacher-school', //
    'DELETE v2/sms/teacher/remove-teacher-subject' => 'v2/sms/teacher/remove-teacher-subject', //


    'POST v2/sms/signup/<type:[a-z]+>' => 'v2/sms/auth/signup', //


    'GET v2/sms/school' => 'v2/sms/school/school', //
    'GET v2/sms/school/subjects' => 'v2/sms/school/subjects', //
    'GET v2/sms/school/classes' => 'v2/sms/school/classes', //
    'GET v2/sms/school/class-subjects' => 'v2/sms/school/class-subjects', //


    'GET v2/sms/teachers' => 'v2/sms/teacher/teachers', //
    'GET v2/sms/students' => 'v2/sms/school/students', //


    //Homework
    'GET v2/sms/class-homework' => 'v2/sms/homework/class-homework', //
];
