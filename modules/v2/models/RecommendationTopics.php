<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "recommendation_topics".
 *
 * @property int $id
 * @property int $recommendation_id
 * @property int $subject_id
 * @property int $student_id
 * @property int|null $object_id
 * @property string|null $object_type This is to let us know if the action is practice or video
 * @property string|null $created_at
 *
 * @property Recommendations $recommendation
 * @property Subjects $subject
 * @property User $student
 */
class RecommendationTopics extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'recommendation_topics';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['recommendation_id', 'subject_id', 'student_id'], 'required'],
            [['recommendation_id', 'subject_id', 'student_id', 'object_id'], 'integer'],
            [['object_type'], 'string'],
            [['created_at'], 'string', 'max' => 45],
            [['recommendation_id'], 'exist', 'skipOnError' => true, 'targetClass' => Recommendations::className(), 'targetAttribute' => ['recommendation_id' => 'id']],
            [['subject_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']],
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
            'recommendation_id' => 'Recommendation ID',
            'subject_id' => 'Subject ID',
            'student_id' => 'Student ID',
            'object_id' => 'Object ID',
            'object_type' => 'Object Type',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Recommendation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRecommendation()
    {
        return $this->hasOne(Recommendations::className(), ['id' => 'recommendation_id']);
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
