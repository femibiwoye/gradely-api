<?php

return [
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