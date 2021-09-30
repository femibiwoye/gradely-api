<?php

namespace app\modules\v2\components;

use Yii;

class SharedConstant
{
    const TYPE_SCHOOL = 'school';
    const TYPE_TEACHER = 'teacher';
    const TYPE_PARENT = 'parent';
    const TYPE_STUDENT = 'student';
    const ACCOUNT_TYPE = ['school', 'teacher', 'parent', 'student', 'tutor','invite'];
    const COUNTRY_CODE = 'NG';
    const STATUS_ACTIVE = 10;
    const STATUS_DELETED = 0;
    const VALUE_ONE = 1;
    const VALUE_TWO = 2;
    const VALUE_ZERO = 0;
    const VALUE_FIVE = 5;
    const VALUE_FOUR = 4;
    const VALUE_THREE = 3;
    const VALUE_SIX = 6;
    const VALUE_NULL = '';
    const SINGLE_TYPE_ARRAY = 'single';
    const MIX_TYPE_ARRAY = 'mix';
    const DEFAULT_COUNTRY = 'Nigeria';
    const FEED_TYPE = 'feed';
    const COMMENT_TYPE = 'comment';
    const TYPE_VIDEO = 'video';
    const FEED_TYPES = ['post', 'announcement', 'homework', 'lesson', 'video', 'image', 'live_class'];
    const HOMEWORK_TYPES = ['homework', 'lesson', 'recommendation'];
    const HOMEWORK_TAG = ['homework', 'exam', 'quiz'];
    const TEACHER_VIEW_BY = ['all', 'class', 'student'];
    const SCHOOL_VIEW_BY = ['all', 'class', 'school', 'teacher', 'parent'];
    const LETTERS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
    const SCHOOL_FORMAT = ['year', 'ss'];
    const SCHOOL_TYPE = ['all', 'primary', 'secondary'];
    const TERMS = ['first', 'second', 'third'];
    const SCHOOL_OWNER_ROLE = 'owner';
    const PRACTICE_TYPES = ['feed', 'practice', 'homework'];
    const QUIZ_SUMMARY_TYPE = ['homework', 'diagnostic'];
    const QUESTION_DIFFICULTY = ['hard', 'medium', 'easy'];
    const SUBSCRIPTION_DURATION = 'month';
    const SUBSCRIPTION_PLAN = 'basic';
    const QUESTION_ACCEPTED_OPTIONS = ['A', 'B', 'C', 'D', 1, 0];
    const PRACTICE_MATERIAL_TYPES = ['video', 'document', 'link', 'image'];
    const RECOMMENDATION_TYPE = ['weekly', 'daily'];
    const WEEKLY_GENERATE_DAY = 'Monday';//'Sunday';
    const REFERENCE_TYPE = ['homework', 'catchup', 'recommendation', 'practice', 'class', 'daily'];
    const PROCTOR_FEEDBACK_TYPE = ['reject_submission', 'admin_report', 'report_error'];
    const PROCTOR_FILE_TYPE = ['image', 'audio', 'video'];
    const TUTOR_SESSION_CATEGORY_TYPE = ['class'];
    const PRACTICE_TYPE = ['single', 'mix'];
    const LIVE_CLASS_USER_TYPE = ['host', 'attendee'];
    const RECOMMENDATION = 'recommendation';
    const PENDING_STATUS = 'pending';
    const PAY_AS_YOU_GO = 'payg';
    const PAID = 'paid';
    const UN_PAID = 'unpaid';
    const VALUE_TWELVE = 12;
    const DEFAULT_CURRICULUM = 1;

    const SINGLE_PRACTICE_QUESTION_COUNT = 5;
    const MIX_PRACTICE_QUESTION_COUNT = 3;

    const DB_CONNECTION_NAME = ['db', 'db_test','dblive'];
    const EXAM_MODE_USER_TYPE = ['parent', 'student'];
    const EXAM_MODES = ['practice', 'exam'];
    const QUESTION_FORMAT = ['multiple', 'bool', 'short'];
}