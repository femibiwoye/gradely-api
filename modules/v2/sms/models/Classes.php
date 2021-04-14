<?php

namespace app\modules\v2\sms\models;

use Yii;

/**
 * This is the model class for table "classes".
 *
 * @property int $id
 * @property string|null $school_id
 * @property int|null $gradely_global_class_id
 * @property string|null $sms_grade
 * @property string|null $gradely_slug
 * @property string|null $name
 * @property string $modified_at
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
            [['gradely_global_class_id'], 'integer'],
            [['modified_at'], 'safe'],
            [['school_id', 'sms_grade', 'gradely_slug', 'name'], 'string', 'max' => 45],
            [['gradely_slug'], 'unique'],
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
            'gradely_global_class_id' => 'Gradely Global Class ID',
            'sms_grade' => 'Sms Grade',
            'gradely_slug' => 'Gradely Slug',
            'name' => 'Name',
            'modified_at' => 'Modified At',
        ];
    }
}
