<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tutor_session".
 *
 * @property int $id
 * @property int $requester_id
 * @property int|null $student_id
 * @property int|null $subject_id
 * @property int $session_count
 * @property int $curriculum_id
 * @property string $category Either paid or covid19
 * @property string|null $availability
 * @property string $status
 * @property string $created_at
 */
class TutorSession extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tutor_session';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['requester_id', 'curriculum_id', 'category'], 'required'],
            [['requester_id', 'student_id', 'subject_id', 'session_count', 'curriculum_id'], 'integer'],
            [['availability', 'created_at'], 'safe'],
            [['status'], 'string'],
            [['category'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'requester_id' => 'Requester ID',
            'student_id' => 'Student ID',
            'subject_id' => 'Subject ID',
            'session_count' => 'Session Count',
            'curriculum_id' => 'Curriculum ID',
            'category' => 'Category',
            'availability' => 'Availability',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
