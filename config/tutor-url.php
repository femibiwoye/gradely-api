<?php

return [

	//Tutor
	'GET v2/tutor' => 'v2/tutor/tutor/index',
	'GET v2/tutor/<tutor_id:\d+>' => 'v2/tutor/tutor/profile',
	'POST v2/tutor/rate' => 'v2/tutor/tutor/tutor-review',
];