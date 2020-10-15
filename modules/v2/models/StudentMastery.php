<?php

namespace app\modules\v2\models;

use Yii;
use yii\base\Model;

class StudentMastery extends User
{

    public $student_id;
    public $subject_id;
    public $class_id;


    public function rules()
    {
        return [
            [['student_id'], 'required', 'when' => function($model) {
                return Yii::$app->user->identity->type == 'teacher';
            }],
            [['student_id', 'class_id', 'subject_id'], 'integer'],
            [['student_id'], 'exist', 'targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']],
            [['subject_id'], 'exist', 'targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']],
            [['class_id'], 'exist', 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
        ];
    }

    public function fields()
    {
        return [
            'id',
            'name' => 'name',
            'image',
            'subjects',
        ];
    }

    private function getName()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public static function find()
    {
        return parent::find()->andWhere(['type' => 'student']);
    }

    private function getSubjects()
    {
        
    }
}