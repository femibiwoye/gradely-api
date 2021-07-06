<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "student_summer_school".
 *
 * @property int $id
 * @property int $student_id
 * @property int|null $parent_id
 * @property int|null $class_id
 * @property int|null $global_class
 * @property int $school_id
 * @property string $subjects This is the course they want to study
 * @property int|null $status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class StudentSummerSchool extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'student_summer_school';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['student_id', 'school_id', 'subjects'], 'required'],
            [['student_id', 'parent_id', 'class_id', 'global_class', 'school_id', 'status'], 'integer'],
            [['subjects', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'student_id' => 'Student ID',
            'parent_id' => 'Parent ID',
            'class_id' => 'Class ID',
            'global_class' => 'Global Class',
            'school_id' => 'School ID',
            'subjects' => 'Subjects',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
