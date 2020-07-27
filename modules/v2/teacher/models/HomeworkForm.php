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
	public $feed_attachments;
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
	public $homework_type;
	public $homework_model;

	public function rules()
	{
		return [
			[['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'slug', 'title', 'topic_id', 'curriculum_id', 'open_date', 'close_date'], 'required'],
			[['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'topic_id', 'curriculum_id'], 'integer'],
			[['open_date', 'close_date'], 'safe'],
			[['slug', 'title', 'homework_type'], 'string', 'max' => 255],
			['class_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'school_id' => 'school_id']],
			['teacher_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'school_id' => 'school_id']],
			['subject_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'school_id' => 'school_id', 'subject_id' => 'subject_id']],
			['school_id', 'exist', 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
			['exam_type_id', 'exist', 'targetClass' => ExamType::className(), 'targetAttribute' => ['exam_type_id' => 'id']],
			['attachments', 'validateAttachment'],
			['feed_attachments', 'validateFeedAttachments'],
			['view_by', 'in', 'range' => SharedConstant::TEACHER_VIEW_BY],
		];
	}

	public function updateHomework($model) {
		$model->attributes = $this->attributes;
		$dbtransaction = Yii::$app->db->beginTransaction();
		try {
			if (!$this->homework_model->save(false)) {
				return false;
			}

			if (!$this->updatePracticeMaterial($model->id)) {
				return false;
			}

			$dbtransaction->commit();
		} catch (Exception $ex) {
			$dbtransaction->rollBack();
			return false;
		}

		return $this->homework_model;
	}

	public function updatePracticeMaterial($homework_id) {
		if (empty($this->attachments)) {
			return true;
		}

		foreach ($this->attachments as $attachment) {
			if (isset($attachment['id'])) {
				$model = PracticeMaterial::findOne(['id' => $attachment['id']]);
			} else {
				$model = new PracticeMaterial;
				$model->user_id = $this->teacher_id;
				$model->practice_id = $homework_id;
			}

			$model->attributes = $attachment;
			if (!$model->save(false)) {
				return false;
			}
		}

		return true;
	}

	public function removeAttachments() {
		$remove_attachment_ids = array_diff(array_column($this->homework_model->practiceMaterials, 'id'), array_column($this->attachments, 'id'));
		if ($remove_attachment_ids) {
			$remove_attachment_ids = array_values($remove_attachment_ids);
			PracticeMaterial::deleteAll(['id' => $remove_attachment_ids]);
		}

		return true;
	}

	public function createHomework() {
		$model = new Homeworks;
		$model->attributes = $this->attributes;
		$model->type = $this->homework_type;
		$dbtransaction = Yii::$app->db->beginTransaction();
		try {
			if (!$model->save(false)) {
				return false;
			}

			if (!$feed = $this->addFeed($model->id)) {
				return false;
			}

			if (!$this->addPracticeMaterial($model->id)) {
				return false;
			}

			if ($this->feed_attachments && !$this->addFeedAttachment($feed->id)) {
				return false;
			}

			$dbtransaction->commit();
		} catch (Exception $ex) {
			$dbtransaction->rollBack();
            return false;
		}

		return $model;
	}

	public function addFeedAttachment($feed_id) {
		foreach ($this->feed_attachments as $feed_attachment) {
			$model = new PracticeMaterial;
			$model->attributes = $feed_attachment;
			$model->user_id = $this->teacher_id;
			$model->practice_id = $feed_id;
			$model->type = SharedConstant::FEED_TYPE;
			if (!$model->save(false)) {
				return false;
			}
		}

		return true;
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
			$model->type = SharedConstant::PRACTICE_TYPES[1];
			if (!$model->save(false)) {
				return false;
			}
		}

		return true;
	}

	public function addFeed($homework_id) {
		$model = new Feed;
		$model->type = $this->homework_type ? $this->homework_type : SharedConstant::FEED_TYPES[2];
		$model->class_id = $this->class_id;
		$model->view_by = $this->view_by; 
		$model->user_id = $this->teacher_id;
		$model->reference_id = $homework_id;
		if (!$model->save(false)) {
			return false;
		}

		return $model;
	}

	public function validateAttachment() {
		if (empty($this->attachments)) {
			return true;
		}

		foreach ($this->attachments as $attachment) {
			if (isset($attachment['id'])) {
				$model = PracticeMaterial::findOne(['id' => $attachment['id']]);
			} else {
				$model = new PracticeMaterial;
				$model->user_id = $this->teacher_id;
			}

			$model->attributes = $attachment;
			if (!$model->validate()) {
				$this->addError($attachment->title . ' is not successfully validated!');
			}
		}

		return true;
	}

	public function validateFeedAttachments() {
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
