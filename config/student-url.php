<?php

return [

    //student profile
    'GET v2/student/parents' => 'v2/student/profile/parents', //
    'GET v2/student/parent-invitations' => 'v2/student/profile/pending-parent-invitations', //
    'PUT v2/student/profile/update-email' => 'v2/student/profile/update-email', //
    'PUT v2/student/profile/update-password' => 'v2/student/profile/update-password', //
    'PUT v2/student/profile' => 'v2/student/profile/update', //
    'PUT v2/student/avatar' => 'v2/student/profile/update-avatar', //

    //student preference
    'GET v2/student/profile/preference' => 'v2/student/preference/index', //
    'PUT v2/student/profile/preference' => 'v2/student/preference/update', //


    //student general
    'GET v2/student/security-questions' => 'v2/student/general/security-questions', //
    'PUT v2/student/security-question' => 'v2/student/general/set-security-question', //

    //student homeworks
    'GET v2/student/completed-homework' => 'v2/student/homework/completed-homework', //
    'GET v2/student/new-homework' => 'v2/student/homework/new-homework', //
    'GET v2/student/homework/score/<homework_id:\d+>' => 'v2/student/homework/homework-score', //
    'GET v2/student/homework/report/<id:\d+>' => 'v2/student/homework/homework-report', //

    //Student Practice
    'GET v2/student/practice/homework-instruction/<homework_id:\d+>' => 'v2/student/practice/homework-instruction', //
    'POST v2/student/practice/start-homework' => 'v2/student/practice/start-homework', //
    'POST v2/student/practice/process-homework' => 'v2/student/practice/process-homework', //
    'GET v2/student/process-attempt/<quiz_id:\d+>' => 'v2/student/practice/process-attempt', //

    //student class
    'GET v2/student/verify-class/<code:[a-zA-Z0-9/]+>' => 'v2/student/class/verify-class', //
    'POST v2/student/class' => 'v2/student/class/student-class', //
    'GET v2/student/class' => 'v2/student/class/student-class-details', //


    //student report
    'GET v2/student/report' => 'v2/student/profile/report', //

    //student catchup
    'GET v2/student/catchup/recent-practice' => 'v2/student/catchup/recent-practice', //
    'GET v2/student/catchup/video-comments/<video_token:[a-zA-Z0-9/]+>' => 'v2/student/catchup/video-comments', //
    'POST v2/student/catchup/video-comment' => 'v2/student/catchup/comment-video', //
    'GET v2/student/catchup/video/<id:\d+>' => 'v2/student/catchup/video', //
    'GET v2/student/catchup/class-resources/<class_id:\d+>' => 'v2/student/catchup/class-resources', //
    'GET v2/student/catchup/video/watch-again/<id:\d+>' => 'v2/student/catchup/watch-video-again', //Not sure what is is meant for
    'GET v2/student/catchup/videos' => 'v2/student/catchup/videos-watched', //
    'POST v2/student/catchup/video-complete/<video_token:[a-zA-Z0-9/]+>' => 'v2/student/catchup/update-video-completed', //
    'PUT v2/student/catchup/video/<video_token:[a-zA-Z0-9/]+>' => 'v2/student/catchup/update-video-length', //
    'GET v2/student/catchup/diagnostic-subjects' => 'v2/student/catchup/diagnostic', //
    'GET v2/student/catchup/recent-practices' => 'v2/student/catchup/recent-practices', //
    'GET v2/student/catchup/incomplete-video' => 'v2/student/catchup/incomplete-videos', //
    'GET v2/student/catchup/practice-materials' => 'v2/student/catchup/class-materials', //
    'GET v2/student/catchup/practice-recommendations' => 'v2/student/catchup/practice-topics', //
    'GET v2/student/catchup/watch-video/<video_token:[a-zA-Z0-9/]+>' => 'v2/student/catchup/watch-video', //
    'POST v2/student/catchup/initialize-practice' => 'v2/student/catchup/initialize-practice', //
    'POST v2/student/catchup/start-practice' => 'v2/student/catchup/start-practice', //
    'POST v2/student/catchup/video-likes/<video_token:[a-zA-Z0-9/]+>' => 'v2/student/catchup/video-likes', //


    //Recommendations
    'GET v2/student/catchup/homework-recommendation/<quiz_id:\d+>' => 'v2/student/recommendation/homework-recommendation',
    'GET v2/student/catchup/generate-weekly-recommendation' => 'v2/student/recommendation/generate-weekly-recommendation',
    'GET v2/student/catchup/weekly-recommendation' => 'v2/student/recommendation/weekly-recommendations',
    'GET v2/student/catchup/generate-daily-recommendation' => 'v2/student/recommendation/generate-daily-recommendations',
    'GET v2/student/catchup/daily-recommendation' => 'v2/student/recommendation/daily-recommendation',

    //student invites
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/invites'], 'extraPatterns' => [
        'POST student-parent' => 'student-parent'
    ]],
];