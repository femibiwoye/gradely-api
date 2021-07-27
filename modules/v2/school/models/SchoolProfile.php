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
    const SCENERIO_EDIT_SCHOOL_CONTACT = 'edit_school_contact';

    public function rules()
    {
        return [
            [['school_type', 'naming_format'], 'required', 'on' => 'format-type'],
            ['naming_format', 'validateFormat', 'on' => 'format-type'],

            [['name', 'tagline', 'address', 'city', 'state', 'country', 'phone', 'school_email', 'contact_name', 'contact_email', 'phone', 'contact_role', 'establish_date'], 'required',
                'on' => self::SCENERIO_EDIT_SCHOOL_PROFILE
            ],
            [['logo', 'about', 'banner', 'website', 'contact_image', 'postal_code', 'boarding_type'], 'safe',
                'on' => self::SCENERIO_EDIT_SCHOOL_PROFILE
            ],
            [['website',
                'contact_email',
                'phone',
                'address',
                'city',
                'state',
                'country'
                ], 'required',
                'on' => self::SCENERIO_EDIT_SCHOOL_CONTACT
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
        unset($sch['id'], $sch['user_id'], $sch['slug'], $sch['naming_format'], $sch['school_type'], $sch['created_at'], $sch['subscription_plan'], $sch['subscription_expiry'], $sch['basic_subscription'], $sch['premium_subscription']);
        $school->attributes = $sch;
        if ($school->save())
            return $school;
        return false;
    }

    public function updateSchoolContact($school)
    {
        $this->scenario = self::SCENERIO_EDIT_SCHOOL_CONTACT;
        $sch = $this->attributes;
        unset($sch['id'], $sch['user_id'], $sch['slug'], $sch['name'], $sch['abbr'], $sch['logo'], $sch['banner'], $sch['tagline'], $sch['about'], $sch['naming_format'], $sch['school_type'], $sch['created_at'], $sch['subscription_plan'], $sch['subscription_expiry'], $sch['basic_subscription'], $sch['premium_subscription']);
        $school->attributes = $sch;
        if ($school->save())
            return $school;
        return false;
    }


}