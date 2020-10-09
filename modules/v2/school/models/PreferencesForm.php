<?php

namespace app\modules\v2\school\models;


use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\Classes;
use app\modules\v2\models\ClassSubjects;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\SchoolSubject;
use app\modules\v2\models\Subjects;
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
    public $classes;

    public $user_id;
    public $role;

    public $password;
    public $timezone;

    public $slug;

    public function rules()
    {
        return [
            [['name', 'country', 'description'], 'required', 'on' => 'curriculum-request'],
            [['curriculum_id'], 'required', 'on' => 'update-curriculum'],
            [['name', 'description'], 'required', 'on' => 'add-subject'],
            [['user_id'], 'required', 'on' => 'update-user'],
            [['user_id', 'role'], 'required', 'on' => 'update-user-role'],
            [['password'], 'required', 'on' => 'verify-password'],
            [['timezone'], 'required', 'on' => 'update-timezone'],

            [['slug'], 'required', 'on' => 'update-slug'],
            ['slug', 'unique', 'targetClass' => 'app\modules\v2\models\Schools', 'message' => 'Address already takens', 'on' => 'update-slug']

        ];
    }


    /**
     * Add new curriculum to school
     * @param $school
     * @return ExamType|bool
     */
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

    /**
     * Create new subject
     * @param $school
     * @return bool|void
     */
    public function addSubject($school, $classes)
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            $count = Classes::find()->where(['id' => $classes, 'school_id' => $school->id])->count();
            if (is_array($classes) && count($classes) == $count) {
                $newModel = new Subjects();
                $newModel->school_id = $school->id;
                $newModel->attributes = $this->attributes;
                $newModel->save();

                $modelSch = new SchoolSubject();
                $modelSch->school_id = $school->id;
                $modelSch->subject_id = $newModel->id;
                if (!$modelSch->save()) {
                    return false;
                }

                foreach ($classes as $key => $class) {
                    $model = new ClassSubjects();
                    $model->class_id = $class;
                    $model->school_id = $school->id;
                    $model->subject_id = $newModel->id;
                    if (!$model->save()) {
                        return false;
                    }
                }
                $dbtransaction->commit();
                return true;
            }
            return false;
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            return $this->addError('name', $e->getMessage());
        }
    }

}