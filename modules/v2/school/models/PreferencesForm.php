<?php

namespace app\modules\v2\school\models;


use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\ExamType;
use Yii;
use yii\base\Model;

/**
 * Password reset request form
 */
class PreferencesForm extends Model
{
    //For curriculum
    public $name;
    public $country;
    public $description;

    public function rules()
    {
        return [
            [['name', 'country','description'], 'required', 'on' => 'curriculum-request'],

        ];
    }


    public function updateFormats($school)
    {
        $newCurriculum = new ExamType();
        $newCurriculum->name = $this->name;
        $newCurriculum->description = $this->description;
        $newCurriculum->country = $this->country;
        $newCurriculum->title = $this->name;
        $newCurriculum->school_id = $school->id;
        if ($newCurriculum->save())
            return $newCurriculum;
        return false;
    }

}