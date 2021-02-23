<?php

namespace app\modules\v2\school\models;


use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\ClassSubjects;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\SchoolCurriculum;
use app\modules\v2\models\Schools;
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

    //For subject linking
    public $subject_id;

    public function rules()
    {
        return [
            [['name', 'country', 'description'], 'required', 'on' => 'curriculum-request'],
            [['curriculum_id'], 'required', 'on' => 'update-curriculum'],
            [['name', 'description'], 'required', 'on' => 'add-subject'],
            [['subject_id', 'classes'], 'required', 'on' => 'link-subject'],
            [['user_id'], 'required', 'on' => 'update-user'],
            [['user_id', 'role'], 'required', 'on' => 'update-user-role'],
            [['password'], 'required', 'on' => 'verify-password'],
            [['timezone'], 'required', 'on' => 'update-timezone'],

            [['slug'], 'required', 'on' => 'update-slug'],
            ['slug', 'unique', 'targetClass' => 'app\modules\v2\models\Schools', 'message' => 'Address already taken', 'on' => 'update-slug']

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
        if (!$newCurriculum->save())
            return false;

        $curriculum = SchoolCurriculum::find()->where(['school_id' => $school->id]);
        if ($curriculum->count() > 1) {
            SchoolCurriculum::deleteAll(['school_id' => $school->id]);
        }
        if ($curriculum = $curriculum->one()) {
            $curriculum->curriculum_id = $newCurriculum->id;
            $curriculum->save();
        } else {
            $new = new SchoolCurriculum();
            $new->curriculum_id = $newCurriculum->id;
            $new->school_id = $school->id;
            $new->save();
        }
        return $newCurriculum;
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

    public function linkSubject($school)
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            $count = Classes::find()->where(['id' => $this->classes, 'school_id' => $school->id])->count();
            if (is_array($this->classes) && count($this->classes) == $count) {
                if (!SchoolSubject::find()->where(['school_id' => $school->id, 'subject_id' => $this->subject_id])->exists()) {
                    $modelSch = new SchoolSubject();
                    $modelSch->school_id = $school->id;
                    $modelSch->subject_id = $this->subject_id;
                    if (!$modelSch->save()) {
                        return false;
                    }
                }


                foreach ($this->classes as $key => $class) {
                    if (!ClassSubjects::find()->where(['school_id' => $school->id, 'subject_id' => $this->subject_id, 'class_id' => $class])->exists()) {
                        $model = new ClassSubjects();
                        $model->class_id = $class;
                        $model->school_id = $school->id;
                        $model->subject_id = $this->subject_id;
                        if (!$model->save()) {
                            return false;
                        }
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

    public function EnsureAndCreateCurriculum(Schools $school)
    {
        if (ExamType::find()->where(['school_id' => $school->id])->exists()) {
            return false;
        }

        $form = new PreferencesForm(['scenario' => 'curriculum-request']);
        $form->name = 'Custom';
        $form->description = 'Custom';
        $form->country = !empty($school->country) ? $school->country : 'Nigeria';
        if (!$model = $form->addCurriculum($school)) {
            return false;
        }
        return $model;
    }

}