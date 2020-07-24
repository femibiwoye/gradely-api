<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "school_role".
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property int $status
 * @property string|null $created_at
 */
class SchoolRole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_role';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['slug', 'title'], 'required'],
            [['status'], 'integer'],
            [['created_at'], 'safe'],
            [['slug', 'title'], 'string', 'max' => 50],
            [['slug'], 'unique'],
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
        ];
    }
}
