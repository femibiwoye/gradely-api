<?php

namespace app\modules\v1\models;

use Yii;

/**
 * This is the model class for table "classes".
 *
 * @property int $id
 * @property int $school_id
 * @property int $global_class_id
 * @property string $slug
 * @property string $class_name e.g Senior secondary School 1
 * @property string $abbreviation
 * @property string $class_code e.g HBY/SSS1
 * @property string $created_at
 */
class Classes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'classes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['school_id', 'global_class_id', 'slug', 'class_name', 'abbreviation', 'class_code'], 'required'],
            [['school_id', 'global_class_id'], 'integer'],
            [['created_at'], 'safe'],
            [['slug', 'class_name'], 'string', 'max' => 255],
            [['abbreviation', 'class_code'], 'string', 'max' => 20],
            [['class_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'school_id' => 'School ID',
            'global_class_id' => 'Global Class ID',
            'slug' => 'Slug',
            'class_name' => 'Class Name',
            'abbreviation' => 'Abbreviation',
            'class_code' => 'Class Code',
            'created_at' => 'Created At',
        ];
    }
}
