<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "homework_questions".
 *
 * @property int $id
 * @property int|null $teacher_id teacher_id could be for teacher and student
 * @property int $homework_id
 * @property int $question_id
 * @property string $difficulty
 * @property int $duration
 * @property string $created_at
 *
 * @property Homeworks $homework
 * @property User $teacher
 */
class HomeworkQuestions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'homework_questions';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'homework_id', 'question_id', 'duration'], 'integer'],
            [['homework_id', 'question_id', 'difficulty', 'duration'], 'required'],
            [['difficulty'], 'string'],
            [['created_at'], 'safe'],
            [['homework_id'], 'exist', 'skipOnError' => true, 'targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id']],
            [['teacher_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['teacher_id' => 'id']],
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
            'homework_id' => 'Homework ID',
            'question_id' => 'Question ID',
            'difficulty' => 'Difficulty',
            'duration' => 'Duration',
            'created_at' => 'Created At',
        ];
    }

//    public function fields()
//    {
//        return ['questions'];
//    }

    /**
     * Gets query for [[Homework]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHomework()
    {
        return $this->hasOne(Homeworks::className(), ['id' => 'homework_id']);
    }

    /**
     * Gets query for [[Teacher]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(User::className(), ['id' => 'teacher_id']);
    }

    /**
     * Gets query for [[Questions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(Questions::className(), ['id'=>'question_id']);
    }
}
