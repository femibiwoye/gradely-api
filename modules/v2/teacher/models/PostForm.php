<?php

namespace app\modules\v2\teacher\models;

use app\modules\v2\components\Utility;
use app\modules\v2\models\Feed;
use app\modules\v2\models\Parents;
use app\modules\v2\models\PracticeMaterial;
use app\modules\v2\models\StudentSchool;
use Yii;
use yii\base\Model;
use app\modules\v2\components\SharedConstant;

/**
 * Post/Discussion/Announcement Form
 */
class PostForm extends Model
{

    public $type;
    public $class_id;
    public $description;
    public $view_by;
    public $attachments;

    public function rules()
    {
        return [
            ['attachments', 'validateAttachment'],
            [['description', 'type', 'class_id', 'view_by' /*, 'attachments'*/], 'required', 'on' => 'teacher'],
            [['description', 'type', 'class_id', 'view_by' /*, 'attachments'*/], 'required', 'on' => 'school'],
            ['view_by', 'in', 'range' => SharedConstant::TEACHER_VIEW_BY, 'on' => 'teacher'],
            ['view_by', 'in', 'range' => SharedConstant::SCHOOL_VIEW_BY, 'on' => 'school'],

            [['description'], 'required', 'on' => 'student-parent'],


        ];
    }


    public function newPost()
    {
        $model = new Feed();
        $model->attributes = $this->attributes;
        $model->user_id = Yii::$app->user->id;
        $userType = Yii::$app->user->identity->type;
        if ($userType == 'student' || $userType == 'parent') {
            $model->type = 'post';
            $model->view_by = 'class';

            if ($userType == 'parent') {
                if (!isset($_GET['child_id']))
                    return false;

                $student = $_GET['child_id'];
                if (!Parents::find()->where(['status' => 1, 'parent_id' => Yii::$app->user->id, 'student_id' => $student])->exists())
                    return false;
            } else
                $student = Yii::$app->user->id;

            $model->class_id = Utility::getStudentClass();
        }

        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save(false)) {
                return false;
            }

            if ($this->attachments && !$this->addAttachments($model)) {
                return false;
            }


            $dbtransaction->commit();
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }

        return $model;
    }

    public function addAttachments($feed)
    {
        foreach ($this->attachments as $file) {
            $model = new PracticeMaterial();
            $model->attributes = $file;
            $model->practice_id = $feed->id;
            $model->user_id = $feed->user_id;
            $model->type = SharedConstant::FEED_TYPE;
            if (!$model->save()) {
                return false;
            }

        }
        return true;
    }


    public function validateAttachment()
    {
        if (empty($this->attachments)) {
            return true;
        }

        foreach ($this->attachments as $attachment) {
            if (isset($attachment['id'])) {
                $model = PracticeMaterial::findOne(['id' => $attachment['id']]);
            } else {
                $model = new PracticeMaterial;
                $model->user_id = Yii::$app->user->id;
            }

            $model->attributes = $attachment;
            if (!$model->validate()) {
                $this->addErrors(['attachments' => $model->errors]);
            }
        }

        return true;
    }

}
