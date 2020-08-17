<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "comprehension".
 *
 * @property int $id
 * @property string $title
 * @property string $body
 * @property int $status
 * @property int $created_by
 * @property int|null $updated_by
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Questions[] $questions
 */
class Comprehension extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'comprehension';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'body', 'created_by'], 'required'],
            [['body'], 'string'],
            [['status', 'created_by', 'updated_by'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'body' => 'Body',
            'status' => 'Status',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Questions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(Questions::className(), ['comprehension_id' => 'id']);
    }
}
