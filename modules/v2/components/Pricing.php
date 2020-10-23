<?php

namespace app\modules\v2\components;

use yii\base\Widget;
use yii\helpers\Html;
use app\modules\v2\models\{Schools, Options, SchoolTeachers, StudentSchool, Parents};

class Pricing extends Widget
{
    public static function ActivateSchoolTrail($id, $type)
    {
    	if ($type == 'teacher') {
    		$school = SchoolTeachers::findOne(['teacher_id' => $id]);
    		$model = Schools::findOne(['user_id' => $school_id]);
    	} else if ($type == 'school') {
    		$model = Schools::findOne(['user_id' => $school_id]);
    	} else if ($type == 'student') {
    		$school = StudentSchool::findOne(['student_id' => $id]);
    		$model = Schools::findOne(['user_id' => $school->school_id]);
    	} else {
    		$school = StudentSchool::find()
    					->innerJoin('parents', 'student_school.student_id = parents.student_id')
    					->where(['parents.parent_id' => $id])
    					->one();

    		$model = Schools::findOne(['user_id' => $school->school_id]);
    	}
    	
    	$model->subscription_plan = self::getSchoolBasicTrail()->name;
    	$model->subscription_expiry = date("Y-m-d", strtotime("+1 month", strtotime(date("Y-m-d"))));
    	if (!$model->save(false)) {
    		return false;
    	}

    	return true;
    }

    private static function getSchoolBasicTrail()
    {
    	return Options::findOne(['name' => SharedConstant::SCHOOL_BASIC_TRAIL, 'value' => SharedConstant::SCHOOL_TRAIL_DAYS]);
    }

    public static function checkSubscriptionStatus($id)
    {
    	$model = Schools::findOne(['user_id' => $id]);

    	if (date("Y-m-d") > $model->subscription_expiry) {
    		return true;
    	}

    	return false;
    }
}