<?php

namespace app\modules\v2\models;

use Yii;

class TutorSession extends \yii\db\ActiveRecord {
	/**
	 * {@inheritdoc}
	 */
	private $new_sessions = [];

	public static function tableName() {
		return 'tutor_session';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules() {
		return [
			[['requester_id', 'category'], 'required'],
			[['requester_id', 'student_id', 'class', 'subject_id', 'session_count', 'curriculum_id', 'is_school'], 'integer'],
			[['repetition', 'preferred_client', 'meeting_token', 'meta', 'status'], 'string'],
			[['availability', 'created_at'], 'safe'],
			[['title'], 'string', 'max' => 200],
			[['category'], 'string', 'max' => 50],
			[['meeting_room'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels() {
		return [
			'id' => 'ID',
			'requester_id' => 'Requester ID',
			'student_id' => 'Student ID',
			'title' => 'Title',
			'repetition' => 'Repetition',
			'class' => 'Class',
			'subject_id' => 'Subject ID',
			'session_count' => 'Session Count',
			'curriculum_id' => 'Curriculum ID',
			'category' => 'Category',
			'availability' => 'Availability',
			'is_school' => 'Is School',
			'preferred_client' => 'Preferred Client',
			'meeting_token' => 'Meeting Token',
			'meeting_room' => 'Meeting Room',
			'meta' => 'Meta',
			'status' => 'Status',
			'created_at' => 'Created At',
		];
	}

	public function getNewSessions() {
		$sessions =  parent::find()->where(['requester_id' => Yii::$app->user->id, 'status' => 'pending'])->orderBy([
			'availability' => SORT_ASC
		])->all();
		foreach ($sessions as $session) {
			if (strtotime($session->availability) <= time() + 604800) {
				$date_array = explode(' ', $session->availability);
				array_push($this->new_sessions, [
					'id' => $session->id,
					'type' => 'live class',
					'title' => $session->title,
					'date' => $date_array[0],
					'time' => $date_array[1] ? $date_array[1] : '',
				]);
			}
		}

		return $this->new_sessions;
	}
}
