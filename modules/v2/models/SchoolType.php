<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "school_type".
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property string $class_range
 * @property int $status
 * @property string $created_at
 */
class SchoolType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'slug', 'title', 'class_range'], 'required'],
            [['id', 'status'], 'integer'],
            [['description'], 'string'],
            [['created_at'], 'safe'],
            [['slug', 'title'], 'string', 'max' => 45],
            [['class_range'], 'string', 'max' => 10],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'slug' => 'Slug',
            'title' => 'Title',
            'description' => 'Description',
            'class_range' => 'Class Range',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
