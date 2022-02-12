<?php

namespace app\modules\v2\school\models;

use app\modules\v2\components\Utility;
use app\modules\v2\models\GlobalClass;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolSubject;
use app\modules\v2\models\Subjects;
use Yii;
use yii\base\Model;
use app\modules\v2\models\Classes;
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class ClassForm extends Classes
{

    const SCENERIO_CREATE_CLASS = 'create_class';
    const SCENERIO_GENERATE_CLASSES = 'generate_class';
    const SCENERIO_UPDATE_CLASS = 'update_class';

    public $school_type;
    public $school_format;

    public function rules()
    {
        return [
            [['global_class_id'], 'exist', 'skipOnError' => true, 'targetClass' => GlobalClass::className(), 'targetAttribute' => ['global_class_id' => 'id']],

            [['global_class_id', 'class_name'], 'required', 'on' => self::SCENERIO_CREATE_CLASS],

            [['school_type', 'school_format'], 'required', 'on' => self::SCENERIO_GENERATE_CLASSES],

            [['id', 'class_name'], 'required', 'on' => self::SCENERIO_UPDATE_CLASS],
        ];
    }

    public function newClass(Schools $school)
    {
        $classes = new Classes();
        $classes->school_id = $school->id;
        $classes->class_name = $this->class_name;
        $classes->global_class_id = $this->global_class_id;
        $classes->abbreviation = strtoupper($this->classAbbr($classes->class_name));


        $lastClass = Classes::find()->where(['school_id' => $school->id, 'global_class_id' => $this->global_class_id])->orderBy('id DESC');
        $lastClassClone = clone $lastClass;


        if ($lastClassClone->exists()) {
            $classShortName = $this->existingClass($school, $lastClass, $this->global_class_id);
            $classes->class_code = strtoupper($school->abbr) . '/' . strtoupper($classShortName[0]) . SharedConstant::LETTERS[$classShortName[1] + 1];
        } else {
            $classShortName = $this->classNames($school, $this->global_class_id);
            $classes->class_code = strtoupper($school->abbr) . '/' . strtoupper($classShortName[0] . $classShortName[1]);
        }

        return $classes->save() ? $classes : false;
    }

    public function updateSchoolSettings(Schools $school, $format, $type)
    {
        $school->naming_format = $format;
        $school->school_type = $type;
        if ($school->save())
            return $school;
        return false;
    }

    public function generateClasses(Schools $school)
    {
        $updatedSchoolModel = $this->updateSchoolSettings($school, $this->school_format, $this->school_type);
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if ($updatedSchoolModel->school_type == 'primary') {
                $classes = GlobalClass::find()->where(['between', 'id', 1, 6])->orWhere(['>', 'id', 12])->andWhere(['status' => 1])->all();
            } elseif ($updatedSchoolModel->school_type == 'secondary') {
                $classes = GlobalClass::find()->where(['between', 'id', 7, 12])->andWhere(['status' => 1])->all();
            } else {
                $classes = GlobalClass::find()->andWhere(['status' => 1])->all();
            }
            foreach ($classes as $class) {
                if (!$this->newClassModel($updatedSchoolModel, $class->id)) {
                    return false;
                }
            }
            $this->populateSubjects($school);
            $dbtransaction->commit();
            return true;
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            \Sentry\captureException($e);
            return $this->addError('class', $e->getMessage());
        }

    }


    public function populateSubjects(Schools $school)
    {
        $types = $school->school_type == 'all' ? ['all', 'primary', 'secondary'] : array_merge(Utility::getCategoryChildren($school->school_type), [$school->school_type, 'all']);
        $subjects = Subjects::find()->where(['status' => 1, 'category' => $types])
            ->andWhere(['OR',['school_id'=>null],['approved'=>1]])
            ->all();

        foreach ($subjects as $subject) {
            if (SchoolSubject::find()->where(['school_id' => $school->id, 'subject_id' => $subject->id])->exists()) {
                continue;
            }
            $model = new SchoolSubject();
            $model->school_id = $school->id;
            $model->subject_id = $subject->id;
            $model->save();
        }
        return true;
    }

    private function newClassModel($school, $globalID)
    {
        if (Classes::find()->where(['school_id' => $school->id, 'global_class_id' => $globalID])->exists()) {
            return true;
        }
        $classNames = $this->classNames($school, $globalID);
        $classes = new Classes();
        $classes->school_id = $school->id;
        $classes->global_class_id = $globalID;
        $classes->class_name = $classNames[2];
        $classes->abbreviation = strtoupper($this->classAbbr($classes->class_name));
        $classes->class_code = strtoupper($school->abbr) . '/' . strtoupper($classNames[0] . $classNames[1]);
        return $classes->save();
    }

    private function existingClass($school, $lastClass, $classID)
    {
        $lastClass = $lastClass->one();
        $shortName = GlobalClass::findOne(['id' => $classID])->class_id;
        if ($school->naming_format == 'ss') {
            $shortName = $this->getShortName($classID);
            $shortName = $shortName[0] . $shortName[1];
        }
        $alphabet = substr($lastClass->class_code, -1);
        return [$shortName, array_search($alphabet, SharedConstant::LETTERS)];
    }


    private function getShortName($classID)
    {
        if ($classID <= 6) {
            $shortName = 'PRY';
            $fullName = 'Primary ';
        } elseif ($classID >= 7 && $classID <= 9) {
            $shortName = 'JSS';
            $fullName = 'Junior secondary school ';
        } elseif ($classID >= 9 && $classID <= 12) {
            $shortName = 'SSS';
            $fullName = 'Senior secondary school ';
        } else {
            $globalClass = GlobalClass::findOne(['id' => $classID]);
            $shortName = $globalClass->class_id;
            $fullName = $globalClass->description;
        }


        switch ($classID) {
            case ($classID == 7 || $classID == 10):
                $year = 1;
                break;
            case  ($classID == 8 || $classID == 11):
                $year = 2;
                break;
            case  ($classID == 9 || $classID == 12):
                $year = 3;
                break;
            case  ($classID > 12):
                $year = null;
                break;
            default:
                $year = $classID;
                break;
        }
        return [$shortName, $year, $fullName . $year];
    }

    private function classNames(Schools $school, $classID)
    {
        if ($school->naming_format == 'ss') {
            $shortNameArray = $this->getShortName($classID);
            $shortName = $shortNameArray[0];
            $year = $shortNameArray[1];
            $fullName = $shortNameArray[2];
        } else {
            $year = $classID <= 12 ? $classID : null;
            $globalClass = GlobalClass::findOne(['id' => $classID]);
            $shortName = $classID <= 12 ? 'Year' : $globalClass->class_id;
            $fullName = $classID <= 12 ? 'Year ' . $year : $globalClass->description;

        }

        return [$shortName, $year, $fullName];
    }

    public function classAbbr($value)
    {

        $lastChar = substr("$value", -1);
        $name = \yii\helpers\Inflector::slug($value);
        $abbr = explode('-', $name, 4);
        $last = '';
        foreach ($abbr as $key => $abr) {
            $abr = $abr == 11 ? $abr : $abr[0];
            $last .= $abr;
        }

        if ($lastChar != substr($last, -1)) {
            return $last . $lastChar;
        }
        return $last;
    }

}