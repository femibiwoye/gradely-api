<?php

return [

    //School Teacher
    'GET v2/school/teacher' => 'v2/school/teacher/index', //
    'GET v2/school/teacher/<class_id:\d*>' => 'v2/school/teacher/index', //
    'GET v2/school/teacher/profile/<teacher_id:\d*>' => 'v2/school/teacher/profile', //
    'GET v2/school/teacher/pending' => 'v2/school/teacher/pending', //
    'PUT v2/school/teacher/accept-teacher/<id:\d+>' => 'v2/school/teacher/accept-teacher', //
    'PUT v2/school/teacher/decline-teacher/<id:\d+>' => 'v2/school/teacher/decline-teacher', //
    'POST v2/school/teacher/assign-subject' => 'v2/school/teacher/assign-subject', //
    'POST v2/school/teacher/assign-class-subject' => 'v2/school/teacher/assign-class-subject', //
    'DELETE v2/school/teacher/class-remove' => 'v2/school/teacher/remove-teacher', //
    'DELETE v2/school/teacher/school-remove' => 'v2/school/teacher/remove-teacher-school', //
    'DELETE v2/school/teacher/class-subject' => 'v2/school/teacher/remove-teacher-subject', //
    'GET v2/school/teacher/pending-invitation' => 'v2/school/teacher/pending-invitation', //


    //School Parents
    'GET v2/school/parents' => 'v2/school/parents', //

    //School General
    'GET v2/school/summary' => 'v2/school/general/summary', //
    'GET v2/school/general/school-type' => 'v2/school/general/school-type', //
    'GET v2/school/general/school-naming-format' => 'v2/school/general/school-naming-format', //
    'GET v2/school/general/school-roles' => 'v2/school/general/school-roles', //
    'PUT v2/school/general/update-format-type' => 'v2/school/general/update-format-type', //
    'POST v2/school/general/request-call' => 'v2/school/general/request-call', //
    'GET v2/school/general/dashboard-todo-status' => 'v2/school/general/dashboard-todo-status', //

    'GET v2/school/week/<type:[a-zA-Z0-9-/]+>' => 'v2/school/general/week', //


    //School class homeworks
    'GET v2/school/homeworks/<class_id:\d+>' => 'v2/school/homework/class-homeworks', //
    'GET v2/school/homework-review/<homework_id:\d+>' => 'v2/school/homework/homework-review', //
    'GET v2/school/homework-performance/<homework_id:\d+>' => 'v2/school/homework/homework-performance', //


    //School Classes
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/school/classes'], 'extraPatterns' => [
        'GET <id:\d+>' => 'view', //
        'GET group-classes' => 'group-classes', //
        'POST generate' => 'generate-classes', //
        'PUT update' => 'update', //
        'GET students/<class_id:\d+>' => 'student-in-class', //
        'DELETE <class_id:\d+>' => 'delete', //
        'GET class-subjects' => 'class-subjects',
        'PUT move-student-class' => 'move-student-class',
        'GET single-class-subjects/<class_id:\d+>' => 'single-class-subjects',
    ]],


    //School Student
    'GET v2/school/student/class-homework/<student_id:\d+>' => 'v2/school/student/student-class-homework', //
    'GET v2/school/student/homework/<student_id:\d+>' => 'v2/school/student/student-homework', //
    //Student profile
    'GET v2/school/student/profile/<student_id:\d+>' => 'v2/school/student/profile', //
    'GET v2/school/student/summary/<student_id:\d+>' => 'v2/school/student/summary', //
    'DELETE v2/school/student/<student_id:\d+>' => 'v2/school/student/remove-student', //
    'GET v2/school/student/<id:\d+>' => 'v2/teacher/class/get-student', //
    'PUT v2/school/student/update-class' => 'v2/school/student/update-class', //
    'GET v2/school/students' => 'v2/school/student/students', //
    'GET v2/school/students/parent/<parent_id:\d+>' => 'v2/school/student/parent-children', //
    'PUT v2/school/students/modify-subscription' => 'v2/school/student/modify-subscription', //

    //School Profile
    'GET v2/school/profile/school' => 'v2/school/profile/school', //
    'PUT v2/school/profile/update-email' => 'v2/school/profile/update-email', //
    'PUT v2/school/profile/update-password' => 'v2/school/profile/update-password', //
    'PUT v2/school/profile' => 'v2/school/profile/update', //
    'GET v2/school/profile/preference' => 'v2/school/profile/preference', //
    'PUT v2/school/profile/preference' => 'v2/school/profile/update-preference', //
    'PUT v2/school/profile/update-school' => 'v2/school/profile/update-school', //
    'DELETE v2/school/profile/delete-personal' => 'v2/school/profile/delete-personal-account', //
    'DELETE v2/school/profile/delete-school' => 'v2/school/profile/delete-school-account', //

    //School contact. Originally for report card
    'GET v2/school/profile/school-contact' => 'v2/school/profile/school-contact', //
    'PUT v2/school/profile/update-contact' => 'v2/school/profile/update-school-contact', //

    //School Preferences
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/school/preferences'], 'extraPatterns' => [
        'GET curriculum' => 'curriculum', //
        'GET subject/<subject_id:\d+>' => 'subject-details', //
        'DELETE subject/<subject_id:\d+>' => 'remove-subject', //
        'GET pending-user' => 'pending-user', //
        'POST new-curriculum' => 'new-curriculum', //
        'PUT update-curriculum' => 'update-curriculum', //
        'GET subjects' => 'subjects', //
        'GET users' => 'users', //
        'POST add-subject' => 'add-subject', //
        'POST link-subject' => 'link-subject', //
        'PUT activate-user' => 'activate-user', //
        'PUT change-user-role' => 'change-user-role', //
        'PUT deactivate-user' => 'deactivate-user', //
        'PUT remove-user' => 'remove-user', //
        'PUT timezone' => 'timezone', //
        'PUT slug' => 'slug', //
        'GET calendar' => 'calendar', //
        'PUT calendar' => 'update-calendar', //
        'PUT reset-calendar' => 'reset-calendar', //
        'GET subject-curriculum' => 'subject-curriculum', //

        'POST assign-class-subjects' => 'assign-class-subjects', //

        'PUT subject/<subject_id:\d+>' => 'edit-subject', //
    ]],

    'GET v2/school/curriculum/topics' => 'v2/school/curriculum/topics', //
    'POST v2/school/curriculum/topic' => 'v2/school/curriculum/create-topic', //
    'DELETE v2/school/curriculum/topic' => 'v2/school/curriculum/delete-topic', //
    'PUT v2/school/curriculum/order-topic' => 'v2/school/curriculum/order-topic', //

    //For population
    'GET v2/school/teacher/populate-class-subjects' => 'v2/school/teacher/populate-class-subjects', //

];