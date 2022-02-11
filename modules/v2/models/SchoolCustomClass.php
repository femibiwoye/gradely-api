<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "school_custom_class".
 *
 * @property int $id
 * @property int $school_id
 * @property string $class_name
 * @property string|null $created_at
 * @property string $class_level
 */
class SchoolCustomClass extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_custom_class';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['school_id', 'class_name', 'class_level'], 'required'],
            [['school_id'], 'integer'],
            [['created_at', 'class_level'], 'safe'],
            [['class_name'], 'string', 'max' => 100],
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
            'class_name' => 'Class Name',
            'created_at' => 'Created At',
            'class_level' => 'Class Level',
        ];
    }
}
