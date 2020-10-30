<?php


//$year = date('Y');
$year = "2021";
$ftBegin = "2020-08-09";

return [
    'first_term_start' => $ftBegin,
    'first_term_end' => $year . "-01-04",
    'second_term_start' => $year . '-01-05',
    'second_term_end' => $year . '-04-18',
    'third_term_start' => $year . '-04-19',
    'third_term_end' => $year . '-09-08',

    //'domain' => Yii::$app->request->BaseUrl,
    'appName' => 'gradely',

    'live_class_secret_token' => LIVE_CLASS_SECRET_TOKEN,

    //Payment gateway
    'payment_sk' => PAYSTACK_SK,
    'payment_pk' => PAYSTACK_PK,

    'baseURl' => 'https://test.gradely.ng',
    'userImage' => "IF(image IS NULL or image = '', null,IF(image LIKE '%http%',image, CONCAT('https://gradely.ng/images/users/',image))) as image",
    'questionImage' => "IF(image IS NULL or image = '', null,IF(image LIKE '%http%',image, CONCAT('https://gradely.ng/images/questions/',image))) as image",
    'subjectImage' => "IF(image IS NULL or image = '', 'https://res.cloudinary.com/gradely/image/upload/v1600773596/placeholders/subjects.png',IF(image LIKE '%http%',image, CONCAT('https://gradely.ng/images/subjects/',image))) as image",
    'topicImage' => "IF(subject_topics.image IS NULL or subject_topics.image = '', 'https://res.cloudinary.com/gradely/image/upload/v1600773596/placeholders/topics.png',IF(subject_topics.image LIKE '%http%',subject_topics.image, CONCAT('https://gradely.ng/images/topics/',subject_topics.image))) as image",

    //WIzItUp url
    'videoDomain' => 'https://api.wizitup.com/reseller/v1/get-link',

    //WizItUp Content Access Key
    'wizItUpKey' => WIZITUP_KEY,

    'AwsS3Key' => AWS_S3_KEY,
    'AwsS3Secret' => AWS_S3_SECRET,

    'live_class_limit' => 20,
    'live_class_url'=>LIVE_CLASS_URL
];
