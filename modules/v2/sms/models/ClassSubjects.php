<?php

namespace app\modules\v2\sms\models;

use Yii;

/**
 * This is the model class for table "class_subjects".
 *
 * @property int $id
 * @property int $school_id
 * @property int|null $gradely_subject_id
 * @property int|null $sms_subject_id
 * @property int $classes_id
 * @property int|null $status
 * @property string $modified_at
 */
class ClassSubjects extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'class_subjects';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['school_id', 'classes_id'], 'required'],
            [['school_id', 'gradely_subject_id', 'sms_subject_id', 'classes_id', 'status'], 'integer'],
            [['modified_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'school_id' => 'School ID',
            'gradely_subject_id' => 'Gradely Subject ID',
            'sms_subject_id' => 'Sms Subject ID',
            'classes_id' => 'Classes ID',
            'status' => 'Status',
            'modified_at' => 'Modified At',
        ];
    }
}
