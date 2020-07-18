<?php
namespace app\modules\v2\teacher\models;

use app\modules\v2\models\Schools;
use Yii;
use yii\base\Model;
use app\modules\v2\models\{Classes};
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class ClassForm extends Classes {

    const SCENERIO_CREATE_CLASS = 'create_class';
    const SCENERIO_UPDATE_CLASS = 'update_class';

    public function rules()
    {
        return [
            [['global_class_id','class_name','class_code'], 'required',
                'on' => self::SCENERIO_CREATE_CLASS
            ],
            [['global_class_id','class_name','abbreviation'], 'required',
                'on' => self::SCENERIO_UPDATE_CLASS
            ]
        ];
    }

    public function newClass(Schools $school)
    {
        $classes->school_id = Utility::getSchoolId();
        $classes->global_class_id = $this->request['global_class_id'];
        $classes->class_name = $this->request['class_name'];
        $classes->class_code = $this->request['class_code'];
        $classes->slug = \yii\helpers\Inflector::slug($this->request['class_name']);
        $classes->abbreviation = Utility::abreviate($classes->slug);

        if ($classes->save()) {
            return[
                'code' => 200,
                'message' => "Successfully created"
            ];
        }
    }

}