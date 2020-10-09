<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "recommendations".
 *
 * @property int $id
 * @property int|null $student_id
 * @property string|null $category Weekly or daily practice/video recommendation interval
 * @property string|null $created_at Let you know if recommendation has been created for today our this week
 * @property string|null $updated_at
 */
class Recommendations extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'recommendations';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['student_id'], 'required'],
            [['id', 'student_id'], 'integer'],
            [['category'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
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
            'student_id' => 'Student ID',
            'category' => 'Category',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'student_id',
            'category',
            'recommendationTopics',
            'created_at',
            'updated_at'
        ];
    }

    public function getRecommendationTopics()
    {
        return $this->hasMany(RecommendationTopics::className(), ['recommendation_id' => 'id']);
    }
}
