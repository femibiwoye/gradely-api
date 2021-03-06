<?php

namespace app\modules\v2\teacher\models;

use app\modules\v2\components\InputNotification;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\HomeworkSelectedStudent;
use app\modules\v2\models\Parents;
use app\modules\v2\models\PracticeTopics;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\SubjectTopics;
use Yii;
use yii\base\Model;
use app\modules\v2\models\{Homeworks, PracticeMaterial, Feed, TeacherClassSubjects, ExamType, Schools};
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class HomeworkForm extends Model
{

    public $practice_attachments;
    public $attachments;
    public $teacher_id;
    public $subject_id;
    public $class_id;
    public $school_id;
    public $title;
    public $topics_id;
    public $open_date;
    public $close_date;
    public $view_by;
    public $homework_model;
    public $tag;
    public $description;
    public $is_proctor;
    public $lesson_description;
    public $selected_student; //If certain students are selected for the assessment
    public $selected_student_id; //ID of the selected students if the id above are valid
    public $bulk_creation_reference;

    public function rules()
    {
        return [
            [['teacher_id', 'subject_id', 'class_id', 'school_id', 'title', 'open_date', 'close_date', 'tag', 'view_by'], 'required', 'on' => 'create-homework'],
            [['teacher_id', 'subject_id', 'class_id', 'school_id', 'title'], 'required', 'on' => 'create-lesson'],
            [['title', 'tag'], 'required', 'on' => 'update-homework'],
            //[['open_date', 'close_date'], 'date', 'format' => 'yyyy-mm-dd '],
            [['teacher_id', 'subject_id', 'class_id', 'school_id', 'selected_student','is_proctor'], 'integer'],
            [['open_date', 'close_date', 'selected_student_id'], 'safe'],
            [['title'], 'string', 'max' => 255],
            ['class_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'school_id' => 'school_id']],
            ['teacher_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'school_id' => 'school_id']],
            ['subject_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id', 'school_id' => 'school_id', 'subject_id' => 'subject_id']],
            ['school_id', 'exist', 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
            [['attachments'], 'validateFeedAttachment'],
            [['practice_attachments'], 'validateAttachment'],
            //validateTopicCurriculum Validates open_date and close_date, tag and topic
            //['topics_id', 'validateTopicCurriculum'],
            ['view_by', 'in', 'range' => SharedConstant::TEACHER_VIEW_BY],
            ['selected_student', 'in', 'range' => [1, 0]],
            [['description', 'lesson_description','bulk_creation_reference'], 'string']
        ];
    }

    public function updateHomework($model)
    {
        //$model->attributes = $this->attributes;
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            $model->title = $this->title;
            $model->tag = $this->tag;
            if (!$model->save(false)) {
                return false;
            }


//            if (!$this->addPracticeTopics($model->id)) {
//                return false;
//            }

//            if (!$this->updatePracticeMaterial($model)) {
//                return false;
//            }

//            $notification = new InputNotification();
//            $teacher = $notification->NewNotification('teacher_set_homework_teacher', [['homework_id', $model->id]]);
//            $student = $notification->NewNotification('teacher_set_homework_student', [['homework_id', $model->id]]);
//            $parent = $notification->NewNotification('teacher_set_homework_parent', [['homework_id', $model->id]]);
//            if (!$student || !$teacher || !$parent)
//                return false;

            $dbtransaction->commit();
        } catch (Exception $ex) {
            $dbtransaction->rollBack();
            \Sentry\captureException($ex);
            return false;
        }

        return $model;
    }

    public function addPracticeTopics($practiceID)
    {
        foreach ($this->topics_id as $topic) {
            $model = new PracticeTopics();
            $model->practice_id = $practiceID;
            $model->topic_id = $topic;
            if (!$model->save()) {
                return false;
            }
        }
    }

    public function updatePracticeMaterial($homework)
    {
        if (empty($this->practice_attachments)) {
            return true;
        }

        foreach ($this->practice_attachments as $attachment) {
            if (isset($attachment['id'])) {
                $model = PracticeMaterial::findOne(['id' => $attachment['id']]);
            } else {
                $model = new PracticeMaterial(['scenario' => 'feed-material']);
                $model->user_id = $homework->teacher_id;
                $model->practice_id = $homework->id;
                $model->type = 'practice';
            }

            $model->attributes = $attachment;
            if (!$model->save(false)) {
                return false;
            }


        }

        return true;
    }

    public function removeAttachments()
    {
        $remove_attachment_ids = array_diff(array_column($this->homework_model->practiceMaterials, 'id'), array_column($this->practice_attachments, 'id'));
        if ($remove_attachment_ids) {
            $remove_attachment_ids = array_values($remove_attachment_ids);
            PracticeMaterial::deleteAll(['id' => $remove_attachment_ids]);
        }

        return true;
    }

    public function createHomework($type)
    {
        $model = new Homeworks(['scenario' => 'assessment']);
        $model->attributes = $this->attributes;
        $model->type = $type;
        //$model->exam_type_id = SubjectTopics::find()->where(['id' => $this->topics_id])->one()->exam_type_id;
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!empty($this->lesson_description))
                $model->description = $this->lesson_description;

            $curriculumStatus = Utility::SchoolActiveCurriculum($model->school_id, true);
            if ($curriculumStatus)
                $model->is_custom_topic = 1;

            if (!$model->save()) {
                return false;
            }

            if ($model->selected_student == 1) {
                if (count($this->selected_student_id) < 1) {
                    return false;
                }

                foreach ($this->selected_student_id as $studentID) {
                    if (!StudentSchool::find()->where(['student_id' => $studentID, 'status' => 1, 'is_active_class' => 1, 'class_id' => $model->class_id])->exists()) {
                        return false;
                    }

                    $selectStudentModel = new HomeworkSelectedStudent();
                    $selectStudentModel->student_id = $studentID;
                    $selectStudentModel->teacher_id = $model->teacher_id;
                    $selectStudentModel->homework_id = $model->id;
                    if (!$selectStudentModel->save()) {
                        false;
                    }
                }
            }

            if (!$feed = $this->addFeed($model)) {
                return false;
            }

            if (!$this->addPracticeMaterial($model->id)) {
                return false;
            }

            if ($this->attachments && !$this->addFeedAttachment($feed->id)) {
                return false;
            }

            $dbtransaction->commit();
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            \Sentry\captureException($ex);
            return false;
        }

        return $model;
    }

    public function addFeedAttachment($feed_id)
    {
        foreach ($this->attachments as $feed_attachment) {
            $model = new PracticeMaterial(['scenario' => 'feed-material']);
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

    public function addPracticeMaterial($homework_id)
    {
        if (empty($this->practice_attachments)) {
            return true;
        }

        foreach ($this->practice_attachments as $attachment) {


            $model = new PracticeMaterial(['scenario' => 'feed-material']);
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

    public function addFeed($homework)
    {
        $model = new Feed;
        $model->type = $homework->type;
        $model->class_id = $this->class_id;
        $model->view_by = $this->view_by;
        $model->user_id = $this->teacher_id;
        $model->reference_id = $homework->id;
        $model->subject_id = $homework->subject_id;
        $model->status = $homework->type == 'homework' ? SharedConstant::VALUE_ZERO : 1;
        if (!empty($this->description))
            $model->description = $this->description;

        if (!$model->save(false)) {
            return false;
        }

        return $model;
    }

    public function validateAttachment($title = null)
    {
        $name = !empty($title) ? $title : 'practice_attachments';

        if (empty($this->$name)) {
            return true;
        }


        foreach ($this->$name as $attachment) {
            if (isset($attachment['id'])) {
                $model = PracticeMaterial::findOne(['id' => $attachment['id']]);
            } else {
                $model = new PracticeMaterial(['scenario' => 'feed-material']);
                $model->user_id = Yii::$app->user->id;
            }

            $model->attributes = $attachment;
            if (!$model->validate()) {
                $this->addErrors([!empty($title) ? $title : 'attachments' => $model->errors]);
            }
        }

        return true;
    }

    public function validateFeedAttachment()
    {
        $this->validateAttachment('attachments');
    }

    public function validateTopicCurriculum()
    {
        if (!in_array($this->tag, SharedConstant::HOMEWORK_TAG)) {
            $this->addError('tag', 'Invalid tag was provided.');
            return false;
        }

        if (!is_array($this->topics_id)) {
            $this->addError('topics_id', 'Topics should be array');
            return false;
        }


        $start_date = strtotime($this->open_date);
        $end_date = strtotime($this->close_date);
        if (empty($end_date) || $end_date < time() || $end_date < $start_date) {
            $this->addError('close_date', 'Date is not valid.');
            return false;
        }


        if (SubjectTopics::find()->where(['id' => $this->topics_id])->groupBy(['exam_type_id'])->count() > 1) {
            $this->addError('topics_id', 'You cannot select topics from different curriculum');
            return false;
        }
        return true;
    }

}
