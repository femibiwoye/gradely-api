<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

/**
 * This is the model class for table "file_log".
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $file_id
 * @property string|null $file_url
 * @property string $type This is to know the type of file.
 * @property int|null $subject_id
 * @property int|null $topic_id
 * @property int|null $class_id
 * @property string|null $total_duration
 * @property string|null $current_duration
 * @property int|null $is_completed
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class FileLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'file_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'file_id', 'subject_id', 'topic_id', 'class_id', 'is_completed', 'current_duration'], 'integer'],
            [['file_url', 'type'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['total_duration'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'file_id' => 'File ID',
            'file_url' => 'File Url',
            'type' => 'Type',
            'subject_id' => 'Subject ID',
            'topic_id' => 'Topic ID',
            'class_id' => 'Class ID',
            'total_duration' => 'Total Duration',
            'current_duration' => 'Current Duration',
            'is_completed' => 'Is Completed',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'user_id',
            'file_id',
            'file',
            'file_url',
            'type',
            'subject',
            'topic',
            'class',
            'total_duration',
            'current_duration',
            'is_completed',
            'created_at',
            'updated_at',
        ];
    }

    public function getFile()
    {
        if ($this->type == SharedConstant::TYPE_VIDEO) {
            $model = VideoContent::findOne(['id' => $this->file_id, 'content_type' => SharedConstant::TYPE_VIDEO]);
            if (!$model) {
                return PracticeMaterial::findOne(['id' => $this->file_id, 'filetype' => SharedConstant::TYPE_VIDEO]);
            }
        } else {
            return PracticeMaterial::findOne(['id' => $this->file_id]);
        }

        return $model;
    }

    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }

    public function getTopic()
    {
        return $this->hasOne(SubjectTopics::className(), ['id' => 'topic_id']);
    }

    public function getClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id']);
    }
}
