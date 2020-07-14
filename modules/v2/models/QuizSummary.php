<?php

namespace app\modules\v2\models;

use Yii;

class QuizSummary extends \yii\db\ActiveRecord {
	/**
	 * {@inheritdoc}
	 */
	public static function tableName() {
		return 'quiz_summary';
	}

	public function rules() {
		return [
			[['homework_id', 'subject_id', 'student_id', 'teacher_id', 'class_id', 'total_questions', 'term', 'topic_id'], 'required'],
			[['homework_id', 'subject_id', 'student_id', 'teacher_id', 'class_id', 'total_questions', 'correct', 'failed', 'skipped', 'submit', 'topic_id'], 'integer'],
			[['type', 'term'], 'string'],
			[['created_at'], 'safe'],
			[['submit_at'], 'string', 'max' => 50],
		];
	}

	public function attributeLabels() {
		return [
			'id' => 'ID',
			'homework_id' => 'Homework ID',
			'subject_id' => 'Subject ID',
			'student_id' => 'Student ID',
			'teacher_id' => 'Teacher ID',
			'class_id' => 'Class ID',
			'type' => 'Type',
			'total_questions' => 'Total Questions',
			'correct' => 'Correct',
			'failed' => 'Failed',
			'skipped' => 'Skipped',
			'term' => 'Term',
			'created_at' => 'Created At',
			'submit_at' => 'Submit At',
			'submit' => 'Submit',
			'topic_id' => 'Topic ID',
		];
	}

	public function fields() {
		return [
			'score',
		];
	}

	public function getScore() {
		return ($this->correct / $this->total_questions) * 100 . '%';
	}
}
