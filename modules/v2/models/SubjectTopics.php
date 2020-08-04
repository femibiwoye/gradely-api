<?php

namespace app\modules\v2\models;

use app\modules\v1\models\QuizSummary;
use Yii;

/**
 * This is the model class for table "subject_topics".
 *
 * @property int $id
 * @property int $subject_id
 * @property int|null $creator_id
 * @property int|null $class_id
 * @property int|null $school_id
 * @property string $slug
 * @property string $topic
 * @property string $description
 * @property int $week_number It contains numbers. 1 stands for week one, 5 stands for week 5
 * @property string $term
 * @property int $exam_type_id
 * @property string|null $image
 * @property int $status
 * @property string $created_at
 *
 * @property CatchupTopics[] $catchupTopics
 * @property PracticeTopics[] $practiceTopics
 * @property VideoAssign[] $videoAssigns
 */
class SubjectTopics extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subject_topics';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subject_id', 'slug', 'topic', 'description', 'week_number', 'term', 'exam_type_id'], 'required'],
            [['subject_id', 'creator_id', 'class_id', 'school_id', 'week_number', 'exam_type_id', 'status'], 'integer'],
            [['description', 'term'], 'string'],
            [['created_at'], 'safe'],
            [['slug', 'topic'], 'string', 'max' => 200],
            [['image'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'subject_id' => 'Subject ID',
            'creator_id' => 'Creator ID',
            'class_id' => 'Class ID',
            'school_id' => 'School ID',
            'slug' => 'Slug',
            'topic' => 'Topic',
            'description' => 'Description',
            'week_number' => 'Week Number',
            'term' => 'Term',
            'exam_type_id' => 'Exam Type ID',
            'image' => 'Image',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[CatchupTopics]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCatchupTopics()
    {
        return $this->hasMany(CatchupTopics::className(), ['topic_id' => 'id']);
    }

    /**
     * Gets query for [[PracticeTopics]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPracticeTopics()
    {
        return $this->hasMany(PracticeTopics::className(), ['topic_id' => 'id']);
    }

    /**
     * Gets query for [[VideoAssigns]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVideoAssigns()
    {
        return $this->hasMany(VideoAssign::className(), ['topic_id' => 'id']);
    }

    public function getTopicPerformanceByID($id,$studentID)
    {
        $model = QuizSummary::find()->where(['topic_id'=>$id,'student_id'=>$studentID]);
        if($model->sum('correct')>0) {
            return $model->sum('correct') / $model->sum('total_questions') * 100;
        }else{
            return 0;
        }
    }
}
