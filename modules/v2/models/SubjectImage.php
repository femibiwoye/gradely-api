<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "subject_image".
 *
 * @property int $id
 * @property int $subject_id
 * @property int $exam_id
 * @property string $image
 * @property string|null $created_by
 * @property string $created_at
 *
 * @property ExamType $exam
 * @property Subjects $subject
 */
class SubjectImage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subject_image';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subject_id', 'exam_id', 'image'], 'required'],
            [['subject_id', 'exam_id'], 'integer'],
            [['image'], 'string'],
            [['created_at'], 'safe'],
            [['created_by'], 'string', 'max' => 50],
            [['exam_id'], 'exist', 'skipOnError' => true, 'targetClass' => ExamType::className(), 'targetAttribute' => ['exam_id' => 'id']],
            [['subject_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'subject_id' => 'Subject ID',
            'exam_id' => 'Exam ID',
            'image' => 'Image',
            'created_by' => 'Created By',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Exam]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExam()
    {
        return $this->hasOne(ExamType::className(), ['id' => 'exam_id']);
    }

    /**
     * Gets query for [[Subject]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }
}
