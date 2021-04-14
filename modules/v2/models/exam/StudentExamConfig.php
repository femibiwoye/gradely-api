<?php

namespace app\modules\v2\models\exam;


use app\modules\v2\models\User;
use Yii;

/**
 * This is the model class for table "student_exam_config".
 *
 * @property int $id
 * @property int $student_id
 * @property int $exam_id
 * @property int $subject_id
 * @property int $status
 * @property string $created_at
 * @property string|null $update_at
 *
 * @property User $student
 */
class StudentExamConfig extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'student_exam_config';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['student_id', 'exam_id', 'subject_id'], 'required'],
            [['student_id', 'exam_id', 'subject_id', 'status'], 'integer'],
            [['created_at', 'update_at'], 'safe'],
            [['student_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']],
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
            'exam_id' => 'Exam ID',
            'subject_id' => 'Subject ID',
            'status' => 'Status',
            'created_at' => 'Created At',
            'update_at' => 'Update At',
        ];
    }

    /**
     * Gets query for [[Student]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudent()
    {
        return $this->hasOne(User::className(), ['id' => 'student_id']);
    }
}
