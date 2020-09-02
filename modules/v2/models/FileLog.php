<?php

namespace app\modules\v2\models;

use Yii;

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
            [['total_duration'], 'string', 'max' => 45],
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
}
