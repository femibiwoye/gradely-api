<?php

return [

	//Tutor
	'GET v2/tutor' => 'v2/tutor/tutor/index',
	'GET v2/tutor/<tutor_id:\d+>' => 'v2/tutor/tutor/profile',
	'POST v2/tutor/rate' => 'v2/tutor/tutor/tutor-review',
	'POST v2/tutor/rate-student' => 'v2/tutor/tutor/tutor-rate-student',
	'POST v2/tutor/booking/single-booking' => 'v2/tutor/booking/single-booking',
];