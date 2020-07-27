<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

class Homeworks extends \yii\db\ActiveRecord
{
	private $homework_annoucements = [];
	public static function tableName()
	{
		return 'homeworks';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'slug', 'title', 'topic_id', 'curriculum_id', 'open_date', 'close_date'], 'required'],
			[['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'topic_id', 'curriculum_id', 'publish_status', 'duration', 'status'], 'integer'],
			[['description', 'access_status'], 'string'],
			[['open_date', 'close_date', 'created_at'], 'safe'],
			[['slug', 'title'], 'string', 'max' => 255],
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
			'subject_id' => 'Subject ID',
			'class_id' => 'Class ID',
			'school_id' => 'School ID',
			'exam_type_id' => 'Exam Type ID',
			'slug' => 'Slug',
			'title' => 'Title',
			'description' => 'Description',
			'topic_id' => 'Topic ID',
			'curriculum_id' => 'Curriculum ID',
			'publish_status' => 'Publish Status',
			'access_status' => 'Access Status',
			'open_date' => 'Open Date',
			'close_date' => 'Close Date',
			'duration' => 'Duration',
			'status' => 'Status',
			'created_at' => 'Created At',
		];
	}

	public function fields() {
		return [
			'id',
			'title',
			'subject',
			'class_id',
			'school_id',
			'exam_type_id',
			'slug',
			'open_date',
			'close_date',
			'score',
			'status' => 'statusMessage',
		];
	}

	public function getSubject() {
		return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
	}

	public function getQuizSummary() {
		return QuizSummary::find()->where(['student_id' => $this->student_id])
            ->andWhere(['teacher_id' => Yii::$app->user->id])
            ->andWhere(['subject_id' => $this->subject->id])
            ->andWhere(['homework_id' => $this->id])->one();

	}

	public function getScore() {
		if (!$this->quizSummary) {
			return '';
		}

		return $this->quizSummary->score;
	}

	public function getStatusMessage() {
		if (($this->score && $this->quizSummary->submit == SharedConstant::VALUE_ONE) && (strtotime($this->close_date) >= time() || strtotime($this->close_date) < time())) {
			return "Closed";
		} else if ((!$this->score || $this->quizSummary->submit != SharedConstant::VALUE_ONE) && strtotime($this->close_date) < time()) {
			return "Expired";
		} else {
			return "Open";
		}
	}

	public function getNewHomeworks() {
		$homeworks =  parent::find()->where(['teacher_id' => Yii::$app->user->id, 'type' => 'homework', ])
							->andWhere(['>', 'open_date', date("Y-m-d")])
							->orderBy(['open_date' => SORT_ASC])
							->all();
		foreach ($homeworks as $homework) {
			if (strtotime($homework->open_date) <= time() + 604800 && strtotime($homework->open_date) >= time()) {
				array_push($this->homework_annoucements, [
					'id' => $homework->id,
					'type' => $homework->type,
					'title' => $homework->title,
					'date_time' => $homework->open_date,
				]);
			}
		}

		return $this->homework_annoucements;
	}

    public function getQuizSummaryRecord() {
    	return $this->hasMany(QuizSummary::className(), ['homework_id' => 'id']);
    }

    public function getRestartHomework() {
    	foreach ($this->quizSummaryRecord as $quizSummary) {
    		if (!$quizSummary->delete()) {
    			return false;
    		}
    	}

    	return true;
    }

    public function getPracticeMaterials() {
    	return $this->hasMany(PracticeMaterial::className(), ['practice_id' => 'id']);
    }

}
