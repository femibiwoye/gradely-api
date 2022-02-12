<?php

Yii::setAlias('@webfolder', realpath(dirname(__FILE__) . '/../web'));
//$year = date('Y');
$year = "2022";
$ftBegin = "2021-08-09";

return [
    'first_term_start' => $ftBegin,
    'first_term_end' => $year . "-01-04",
    'second_term_start' => $year . '-01-05',
    'second_term_end' => $year . '-04-18',
    'third_term_start' => $year . '-04-19',
    'third_term_end' => $year . '-08-08',

    //'domain' => Yii::$app->request->BaseUrl,
    'appName' => 'gradely',

    'live_class_secret_token' => LIVE_CLASS_SECRET_TOKEN,

    //Payment gateway
    'payment_sk' => PAYSTACK_SK,
    'payment_pk' => PAYSTACK_PK,

    'baseURl' => 'https://live.gradely.ng',
    'userImage' => "IF(image IS NULL or image = '', null,IF(image LIKE '%http%',image, CONCAT('https://live.gradely.ng/images/users/',image))) as image",
    'questionImage' => "IF(image IS NULL or image = '', null,IF(image LIKE '%http%',image, CONCAT('https://live.gradely.ng/images/questions/',image))) as image",
    'subjectImage' => "IF(image IS NULL or image = '', 'https://gradly.s3.eu-west-2.amazonaws.com/placeholders/subject.png',IF(image LIKE '%http%',image, CONCAT('https://live.gradely.ng/images/subjects/',image))) as image",
    'topicImage' => "IF(subject_topics.image IS NULL or subject_topics.image = '', 'https://gradly.s3.eu-west-2.amazonaws.com/placeholders/topic.png',IF(subject_topics.image LIKE '%http%',subject_topics.image, CONCAT('https://live.gradely.ng/images/topics/',subject_topics.image))) as image",
    'ThumbnailQuery' => "IF(subject_topics.image IS NULL or subject_topics.image = '', 'https://gradly.s3.eu-west-2.amazonaws.com/placeholders/topic.png',IF(subject_topics.image LIKE '%http%',subject_topics.image, CONCAT('https://live.gradely.ng/images/topics/',subject_topics.image))) as image",

    //WIzItUp url
    'videoDomain' => 'https://api.wizitup.com/reseller/v1/get-link',

    //WizItUp Content Access Key
    'wizItUpKey' => WIZITUP_KEY,

    'AwsS3Key' => AWS_S3_KEY,
    'AwsS3Secret' => AWS_S3_SECRET,
    'AwsS3BucketName' => 'gradly',

    'live_class_limit' => 1000,
    'live_class_url' => LIVE_CLASS_URL,
    'live_class_recorded_url' => LIVE_CLASS_RECORDED_URL,

    'masteryQuestionCount' => 6,
    'masteryPerTopicPerformance' => 100,
    'masteryPerTopicUnit' => 1,

    'topicQuestionsMin' => 10,

    'superPassword' => SUPER_PASSWORD,

    'customError401' => [
        "name" => "Unauthorized",
        "message" => "Your request was made with invalid credentials.",
        "code" => 0,
        "status" => 401],

    'appBase' => APP_BASE_URL,
    'tutorAppBase' => TUTOR_BASE_URL,

    'practiceQuestionCount' => ['single' => 5, 'mix' => 3],
    'examQuestionCount' => ['single' => 8, 'mix' => 5],

    'summerSchoolID' => SUMMER_SCHOOL_ID,
    'activeSession' => ACTIVE_SESSION,
    'appFolderLevel' => APP_FOLDER_LEVEL, // For S3 bucket. It enable separation of files created from local, appdev, tapp, and app application
    'applicationStage'=>APPLICATION_STAGE,

    'bbbSecret' => BBB_SECRET,
    'bbbServerBaseUrl' => BBB_SERVER_BASE_URL,
    'liveClassClient' => LIVE_CLASS_CLIENT,
    'auth2.1Secret'=>AUTH_NEW_SECRET
];
