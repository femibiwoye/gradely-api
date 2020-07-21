<?php
$year = date('Y');
Yii::setAlias('@userImgPath', 'f:\xampp\htdocs\v2-api\web\images\users');
Yii::setAlias('@userImgUrl', 'http://localhost/v2-api/images/users');
return [
    'first_term_start' => $year . '-09-09',
    'first_term_end' => $year . '-01-05',
    'second_term_start' => $year . '-01-06',
    'second_term_end' => $year . '-04-06',
    'third_term_start' => $year . '-04-07',
    'third_term_end' => $year . '-09-08',
];
