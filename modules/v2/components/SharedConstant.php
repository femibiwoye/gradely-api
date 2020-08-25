<?php

namespace app\modules\v2\components;

use Yii;

class SharedConstant {
	const TYPE_SCHOOL = 'school';
	const TYPE_TEACHER = 'teacher';
	const TYPE_PARENT = 'parent';
	const TYPE_STUDENT = 'student';
	const ACCOUNT_TYPE = ['school', 'teacher', 'parent', 'student', 'tutor'];
	const COUNTRY_CODE = 'NG';
	const STATUS_ACTIVE = 10;
	const STATUS_DELETED = 0;
	const VALUE_ONE = 1;
	const VALUE_ZERO = 0;
	const DEFAULT_COUNTRY = 'Nigeria';
	const FEED_TYPE = 'feed';
	const COMMENT_TYPE = 'comment';
	const FEED_TYPES = ['post', 'announcement', 'homework', 'lesson', 'video', 'image'];
	const HOMEWORK_TYPES = ['homework', 'lesson'];
	const HOMEWORK_TAG = ['homework', 'exam', 'quiz'];
	const TEACHER_VIEW_BY = ['all', 'class', 'student'];
	const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
	const SCHOOL_FORMAT = ['year','ss'];
	const SCHOOL_TYPE = ['all','primary','secondary'];
	const SCHOOL_OWNER_ROLE = 'owner';
	const PRACTICE_TYPES = ['feed', 'practice'];
	const QUIZ_SUMMARY_TYPE = ['homework'];
	const QUESTION_DIFFICULTY = ['hard', 'medium', 'easy'];
	const SUBSCRIPTION_DURATION = 'month';
	const SUBSCRIPTION_PLAN = 'basic';
}