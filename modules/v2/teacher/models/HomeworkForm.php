<?php
namespace app\modules\v2\teacher\models;

use Yii;
use yii\base\Model;
use app\modules\v2\models\{Homeworks, PracticeMaterial, Feed, TeacherClassSubjects, ExamType, Schools};
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class HomeworkForm extends Model {

	public $attachments;
	public $teacher_id;
	public $subject_id;
	public $class_id;
	public $school_id;
	public $exam_type_id;
	public $slug;
	public $title;
	public $topic_id;
	public $curriculum_id;
	public $open_date;
	public $close_date;
	public $view_by;

	public function rules()
	{
		return [
			[['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'slug', 'title', 'topic_id', 'curriculum_id', 'open_date', 'close_date'], 'required'],
			[['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'topic_id', 'curriculum_id'], 'integer'],
			[['open_date', 'close_date'], 'safe'],
			[['slug', 'title'], 'string', 'max' => 255],
			['class_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'school_id' => 'school_id']],
			['teacher_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'school_id' => 'school_id']],
			['subject_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'school_id' => 'school_id', 'subject_id' => 'subject_id']],
			['school_id', 'exist', 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
			['exam_type_id', 'exist', 'targetClass' => ExamType::className(), 'targetAttribute' => ['exam_type_id' => 'id']],
			['attachments', 'validateAttachment'],
			['view_by', 'in', 'range' => SharedConstant::TEACHER_VIEW_BY],
		];
	}

	public function createHomework() {
		$model = new Homeworks;
		$model->attributes = $this->attributes;
		$dbtransaction = Yii::$app->db->beginTransaction();
		try {
			if (!$model->save(false)) {
				return false;
			}

			if (!$this->addPracticeMaterial($model->id)) {
				return false;
			}

			if (!$this->addFeed()) {
				return false;
			}

			$dbtransaction->commit();
		} catch (Exception $ex) {
			$dbtransaction->rollBack();
            return false;
		}

		return $model;
	}

	public function addPracticeMaterial($homework_id) {
		if (empty($this->attachments)) {
			return true;
		}

		foreach ($this->attachments as $attachment) {
			$model = new PracticeMaterial;
			$model->attributes = $attachment;
			$model->user_id = $this->teacher_id;
			$model->practice_id = $homework_id;
			if (!$model->save(false)) {
				return false;
			}
		}

		return true;
	}

	public function addFeed() {
		$model = new Feed;
		$model->type = SharedConstant::FEED_TYPES[2];
		$model->class_id = $this->class_id;
		$model->view_by = $this->view_by; 
		$model->user_id = $this->teacher_id;
		if (!$model->save(false)) {
			return false;
		}

		return true;
	}

	public function validateAttachment() {
		if (empty($this->attachments)) {
			return true;
		}

		foreach ($this->attachments as $attachment) {
			$model = new PracticeMaterial;
			$model->attributes = $attachment;
			$model->user_id = $this->teacher_id;
			if (!$model->validate()) {
				$this->addError($attachment->title . ' is not successfully validated!');
			}
		}

		return true;
	}
	
}
