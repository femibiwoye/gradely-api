<?php

return [
    'POST v2/login' => 'v2/auth/login',
    'POST v2/signup/<type:[a-z]+>' => 'v2/auth/signup',
    'POST v2/logout' => 'v2/auth/logout',
    'POST v2/forgot-password' => 'v2/auth/forgot-password',
    'POST v2/reset-password' => 'v2/auth/reset-password',
    'PUT v2/teacher/profile/update-email' => 'v2/teacher/profile/update-email',
    'PUT v2/teacher/profile/update-password' => 'v2/teacher/profile/update-password', 
    'PUT v2/teacher/profile' => 'v2/teacher/profile/update',
    'PUT v2/teacher/profile/preference' => 'v2/teacher/profile/preference',

	['class' => 'yii\rest\UrlRule', 'controller' => ['modules\v2\auth'], 'extraPatterns' => [
		'POST login' => 'login',
		'OPTIONS login' => 'login',
		'POST reset-password' => 'reset-password',
		'OPTIONS reset-password' => 'reset-password',
		'POST forgot-password' => 'forgot-password',
		'OPTIONS forgot-password' => 'forgot-password',
		'GET logout' => 'logout',
		'OPTIONS logout' => 'options',			
	]],
	['class' => 'yii\rest\UrlRule', 'controller' => ['module\v2\signup'], 'extraPatterns' => [
		'POST create' => 'create',
		'OPTIONS signup' => 'options',
	]],
];