<?php

return [
    //Tutor
    'POST v2/sms/class/map-class-subject' => 'v2/sms/class/map-class-subject',
    'GET v2/sms/class/get-class-subjects' => 'v2/sms/class/get-class-subjects',

    'POST v2/sms/class/create-class-arm' => 'v2/sms/class/create-class-arm',
    'PUT v2/sms/class/update-class-arm' => 'v2/sms/class/update-class-arm',
    'GET v2/sms/class/get-global-class-arms' => 'v2/sms/class/get-global-class-arms',
    'GET v2/sms/class/class-teacher-subjects' => 'v2/sms/class/class-teacher-subjects',

    'POST v2/sms/teacher/assign-subject-to-teacher' => 'v2/sms/teacher/assign-subject-to-teacher', //
    'POST v2/sms/teacher/assign-subjects-to-teacher' => 'v2/sms/teacher/assign-subjects-to-teacher', //
    'DELETE v2/sms/teacher/remove-teacher-from-class' => 'v2/sms/teacher/remove-class-teacher', //
    'DELETE v2/sms/teacher/remove-teacher-from-school' => 'v2/sms/teacher/remove-teacher-school', //
    'DELETE v2/sms/teacher/remove-teacher-subject' => 'v2/sms/teacher/remove-teacher-subject', //


    'POST v2/sms/signup/<type:[a-z]+>' => 'v2/sms/auth/signup', //
    'POST v2/sms/login/<id:\d+>' => 'v2/sms/auth/login', //

    'POST v2/sms/validate' => 'v2/sms/validate/validate', //


    'GET v2/sms/school' => 'v2/sms/school/school', //
    'GET v2/sms/school/subjects' => 'v2/sms/school/subjects', //
    'POST v2/sms/school/subject' => 'v2/sms/school/add-subject', //
    'GET v2/sms/school/classes' => 'v2/sms/school/classes', //
    'GET v2/sms/school/class-subjects' => 'v2/sms/school/class-subjects', //


    'PUT v2/sms/class/update-student-class' => 'v2/sms/class/update-student-class', //


    'GET v2/sms/teachers' => 'v2/sms/teacher/teachers', //
    'GET v2/sms/teachers/find-teacher' => 'v2/sms/teacher/find-teacher', //
    'GET v2/sms/students' => 'v2/sms/school/students', //


    //Homework
    'GET v2/sms/class-homework' => 'v2/sms/homework/class-homework', //
    'GET v2/sms/homework-review/<homework_id:\d+>' => 'v2/sms/homework/homework-review', //
    'GET v2/sms/homework-performance/<homework_id:\d+>' => 'v2/sms/homework/homework-performance', //

    'POST v2/sms/student/join-class' => 'v2/sms/class/join-class', //
    'POST v2/sms/school/link-subject-to-school' => 'v2/sms/school/link-subject', //
    'POST v2/sms/school/map-orphan-teacher' => 'v2/sms/auth/connect-new-teacher' //
];
