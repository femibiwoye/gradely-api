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
            [['id', 'student_id', 'is_taken'], 'integer'],
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
            'is_taken' => 'Is taken',
            'reference_type' => 'Reference Type',
            'reference_id' => 'Reference Id',
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
            'is_taken' => 'isTaken',
            'reference_type',
            'reference_id',
            'recommendationTopics',
            'created_at',
            'updated_at'
        ];
    }

    public function getRecommendationTopics()
    {
        return $this->hasMany(RecommendationTopics::className(), ['recommendation_id' => 'id']);
    }

    public function getIsTaken()
    {

        $model = Homeworks::find()
                    ->innerJoin(['quiz_summary', 'quiz_summary.homework_id = homeworks.id'])
                    ->where(['homeworks.id' => $this->reference_id, 'quiz_summary.submit' => 1]);

        if ($model) {
            return 1;
        }

        return 0;

    }
}
