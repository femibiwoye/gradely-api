<?php

namespace app\modules\v2\models;


use app\modules\v2\components\Utility;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Password reset request form
 */
class TeacherProfile extends User
{

    public function fields()
    {
        return [
            'id',
            'firstname',
            'lastname',
            'image',
            'email',
            'phone',
            'type',
            'profile' => 'userProfile',
            'subjects',
            'classes',
            'homework'
        ];
    }

    public function getSubjects()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $subjects = ArrayHelper::getColumn(TeacherClassSubjects::find()
            ->where(['school_id' => $school->id, 'teacher_id' => $this->id, 'status' => 1])
            ->groupBy(['subject_id'])
            ->all(), 'subject_id');
        return Subjects::find()->where(['id' => $subjects])->select(['id', 'slug', 'name'])->all();
    }

    public function getClasses()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $classes = ArrayHelper::getColumn(TeacherClass::find()->where(['school_id' => $school->id, 'teacher_id' => $this->id, 'status' => 1])->all(), 'class_id');
        $classes = Classes::find()
            ->where(['id' => $classes])->all();
        $returns = [];
        foreach ($classes as $class) {
            $subjects = ArrayHelper::getColumn(TeacherClassSubjects::find()
                ->where(['school_id' => $class->school_id, 'teacher_id' => $this->id, 'status' => 1, 'class_id' => $class->id])
                ->groupBy(['subject_id'])
                ->all(), 'subject_id');
            $returns[] = array_merge(ArrayHelper::toArray($class), ['subjects' => Subjects::find()->where(['id' => $subjects])->select(['id', 'slug', 'name'])->all()]);
        }
        return $returns;
    }

    public function getHomework()
    {
        $model = Homeworks::find()->andWhere(['teacher_id' => $this->id, 'type' => 'homework', 'status' => 1, 'publish_status' => 1])->all();
        return $model;
    }

}
