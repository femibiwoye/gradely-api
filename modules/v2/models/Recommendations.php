<?php

namespace app\modules\v2\models;

use Yii;

/**
 * @property int $id
 * @property int|null $student_id
 * @property string|null $category Weekly or daily practice/video recommendation interval
 * @property int|null $is_taken If this recommendation has been consumed
 * @property string|null $reference_type e.g homework, diagnostic
 * @property int|null $reference_id Id of the assessment
 * @property string|null $type This is used to identify if recommendation is practice, video or tutor
 * @property int|null $resource_count Question count or video count
 * @property string|null $raw This contains the whole content of each recommendation
 * @property string|null $created_at Let you know if recommendation has been created for today our this week
 * @property string|null $updated_at
 *
 * @property RecommendationTopics[] $recommendationTopics
 * @property User $student
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
            [['student_id', 'is_taken', 'reference_id', 'resource_count'], 'integer'],
            [['category', 'type'], 'string'],
            [['raw', 'created_at', 'updated_at'], 'safe'],
            [['reference_type'], 'string', 'max' => 50],
            [['student_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']],
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

//    public function fields()
//    {
//        return [
//            'id',
//            'student_id',
//            'category',
//            'is_taken' => 'isTaken',
//            'reference_type',
//            'reference_id',
//            'recommendationTopics',
//            'created_at',
//            'updated_at'
//        ];
//    }

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
