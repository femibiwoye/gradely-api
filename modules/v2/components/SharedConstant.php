<?php

namespace app\modules\v2\components;

use Yii;

class SharedConstant {
	const TYPE_SCHOOL = 'school';
	const TYPE_TEACHER = 'teacher';
	const TYPE_PARENT = 'parent';
	const TYPE_STUDENT = 'student';
	const ACCOUNT_TYPE = ['school','teacher','parent','student'];
	const COUNTRY_CODE = 'NG';
	const STATUS_ACTIVE = 10;
	const STATUS_DELETED = 0;
	const VALUE_ONE = 1;
}