<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "catchup".
 *
 * @property int $id
 * @property int $student_id
 * @property int $subject_id
 * @property int|null $exam_type_id
 * @property int|null $class_id
 * @property int|null $school_id
 * @property int $question_count how many question to be attempted
 * @property int $duration duration is in minutes
 * @property string $type
 * @property int|null $generator_id i'm using "generator_id" instead of "parent_id" to avoid restriction incase teachers will be allowed to generate catchup/diagnostic test.
 * @property int $status 0 means incomplete and 1 means completed
 * @property string $created_at
 *
 * @property User $student
 * @property CatchupDifficulty[] $catchupDifficulties
 * @property CatchupQuestions[] $catchupQuestions
 * @property CatchupTopics[] $catchupTopics
 */
class Catchup extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'catchup';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['student_id', 'question_count'], 'required'],
			[['student_id', 'subject_id', 'exam_type_id', 'class_id', 'school_id', 'question_count', 'duration', 'generator_id', 'status'], 'integer'],
			[['type'], 'string'],
			[['created_at'], 'safe'],
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
			'subject_id' => 'Subject ID',
			'exam_type_id' => 'Exam Type ID',
			'class_id' => 'Class ID',
			'school_id' => 'School ID',
			'question_count' => 'Question Count',
			'duration' => 'Duration',
			'type' => 'Type',
			'generator_id' => 'Generator ID',
			'status' => 'Status',
			'created_at' => 'Created At',
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

	/**
	 * Gets query for [[CatchupDifficulties]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCatchupDifficulties()
	{
		return $this->hasMany(CatchupDifficulty::className(), ['catchup_id' => 'id']);
	}

	/**
	 * Gets query for [[CatchupQuestions]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCatchupQuestions()
	{
		return $this->hasMany(CatchupQuestions::className(), ['quiz_id' => 'id']);
	}

	/**
	 * Gets query for [[CatchupTopics]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCatchupTopics()
	{
		return $this->hasMany(CatchupTopics::className(), ['catchup_id' => 'id']);
	}
}
