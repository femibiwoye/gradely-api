<?php

namespace app\modules\v2\school\models;


use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\Schools;
use Yii;

/**
 * Password reset request form
 */
class SchoolProfile extends Schools
{
    const SCENERIO_EDIT_SCHOOL_PROFILE = 'edit_school_profile';

    public function rules()
    {
        return [
            [['school_type', 'naming_format'], 'required', 'on' => 'format-type'],
            ['naming_format', 'validateFormat', 'on' => 'format-type'],

            [['name', 'about', 'address', 'city', 'state', 'country', 'phone', 'school_email', 'contact_name', 'contact_email', 'phone', 'contact_role', 'establish_date'], 'required',
                'on' => self::SCENERIO_EDIT_SCHOOL_PROFILE
            ]
        ];
    }

    public function validateFormat()
    {
        if (!in_array($this->school_type, SharedConstant::SCHOOL_TYPE))
            $this->addError('school_type', 'You provided invalid school type');

        if (!in_array($this->naming_format, SharedConstant::SCHOOL_FORMAT))
            $this->addError('naming_format', 'You provided invalid school format');

        return false;
    }

    public function updateFormats($school)
    {
        $school->naming_format = $this->naming_format;
        $school->school_type = $this->school_type;
        if ($school->save())
            return $school;
        return false;
    }

    public function updateSchool($school)
    {
        $this->scenario = self::SCENERIO_EDIT_SCHOOL_PROFILE;
        $sch = $this->attributes;
        unset($sch['id'], $sch['user_id'], $sch['slug'], $sch['naming_format'], $sch['school_type'], $sch['created_at']);
        $school->attributes = $sch;
        if ($school->save())
            return $school;
        return false;
    }


}