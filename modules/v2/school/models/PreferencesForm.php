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

    public $curriculum_id;

    public function rules()
    {
        return [
            [['name', 'country','description'], 'required', 'on' => 'curriculum-request'],
            [['curriculum_id'], 'required', 'on' => 'update-curriculum'],

        ];
    }


    public function addCurriculum($school)
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