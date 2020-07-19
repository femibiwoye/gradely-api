<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "school_naming_format".
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property int $status
 * @property string $created_at
 * @property string|null $updated_at
 */
class SchoolNamingFormat extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_naming_format';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['slug', 'title'], 'required'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['slug'], 'string', 'max' => 10],
            [['title'], 'string', 'max' => 45],
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
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
