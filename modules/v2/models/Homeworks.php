<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "homeworks".
 *
 * @property int $id
 * @property int|null $student_id
 * @property int|null $teacher_id
 * @property int $subject_id
 * @property int|null $class_id
 * @property int|null $school_id
 * @property int $exam_type_id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property int|null $topic_id
 * @property int|null $curriculum_id
 * @property int $publish_status
 * @property string $access_status
 * @property string|null $open_date
 * @property string|null $close_date
 * @property int|null $duration Duration should be in minutes
 * @property string $type Homework is students regular take home. Practice is students self created assessment. Diagnostic is an auto-generated assessment to know the level of the child. Recommendation is a suggested/recommended practice/material/videos to help improve their level of knowledge. Catchup is a gamified practice. Lesson is a material created by teacher for student to learn.
 * @property string|null $tag Tag is used to identify homework sub category. Maybe it is an homework, quiz or exam
 * @property int $status
 * @property string $created_at
 *
 * @property Feed[] $feeds
 * @property HomeworkQuestions[] $homeworkQuestions
 * @property PracticeTopics[] $practiceTopics
 */
class Homeworks extends \yii\db\ActiveRecord
{
	private $homework_annoucements = [];
	public static function tableName()
	{
		return 'homeworks';
	}

    public function behaviors()
    {
        return [
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'title',
                'ensureUnique' => true,
            ]
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['teacher_id', 'subject_id', 'class_id', 'school_id', 'slug', 'title'], 'required'],
			[['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'topic_id', 'curriculum_id', 'publish_status', 'duration', 'status', 'exam_type_id'], 'integer'],
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
			'status' => 'statusMessage', //this is used to be student to know if homework is open, expired or closed
			'expiry_status' => 'expiryStatus',
			'publish_status' => 'publishStatus',
            'topics',
            'attachments'
		];
	}

    public static function find()
    {
        return parent::find()->where(['status' => 1]);
    }

	public function getExpiryStatus() {
		if (time() > strtotime($this->close_date)) {
			return 'closed';
		} else {
			return 'open';
		}
	}

	public function getPublishStatus() {
		return $this->publish_status;
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
			return null;
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

    public function getTopicsID()
    {
        return $this->hasMany(PracticeTopics::className(),['practice_id'=>'id']);
    }

    public function getTopics()
    {
        return $this->hasMany(SubjectTopics::className(),['id'=>'topic_id'])->via('topicsID');
    }

    public function getAttachments()
    {
        return $this->hasMany(PracticeMaterial::className(),['practice_id'=>'id'])->andWhere(['type'=>'practice']);
    }

}
