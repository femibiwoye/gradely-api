<?php

namespace app\modules\v2\teacher\models;

use app\modules\v2\models\Feed;
use app\modules\v2\models\PracticeMaterial;
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
            [['description', 'type', 'class_id', 'view_by', 'attachments'], 'required', 'on' => 'new-post'],
            ['view_by', 'in', 'range' => SharedConstant::TEACHER_VIEW_BY, 'on' => 'new-post'],


        ];
    }


    public function newPost()
    {
        $model = new Feed();
        $model->attributes = $this->attributes;
        $model->user_id = Yii::$app->user->id;

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
                $this->addErrors(['attachments'=>$model->errors]);
            }
        }

        return true;
    }

}
