<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "report_error".
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $school
 * @property string|null $class
 * @property int|null $reference_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $type
 * @property int $status
 * @property string $created_at
 */
class ReportError extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'report_error';
    }

    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['reference_id', 'title', 'description'], 'required', 'on' => 'question-report'],
            [['user_id', 'reference_id', 'status'], 'integer'],
            [['description', 'type'], 'string'],
            ['type', 'default', 'value' => 'question'],
            [['created_at'], 'safe'],
            [['school', 'class', 'title'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'school' => 'School',
            'class' => 'Class',
            'reference_id' => 'Reference ID',
            'title' => 'Title',
            'description' => 'Description',
            'type' => 'Type',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
