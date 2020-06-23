<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "school_teachers".
 *
 * @property int $id
 * @property int $teacher_id
 * @property int $school_id
 * @property int $status
 * @property string $created_at
 */
class SchoolTeachers extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_teachers';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'school_id'], 'required'],
            [['teacher_id', 'school_id', 'status'], 'integer'],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'teacher_id' => 'Teacher ID',
            'school_id' => 'School ID',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
