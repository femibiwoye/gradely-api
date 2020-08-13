<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "practice_topics".
 *
 * @property int $id
 * @property int $practice_id
 * @property int $topic_id
 * @property string $created_at
 *
 * @property Homeworks $practice
 * @property SubjectTopics $topic
 */
class PracticeTopics extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'practice_topics';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['practice_id', 'topic_id'], 'required'],
            [['practice_id', 'topic_id'], 'integer'],
            [['created_at'], 'safe'],
            [['practice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Homeworks::className(), 'targetAttribute' => ['practice_id' => 'id']],
            [['topic_id'], 'exist', 'skipOnError' => true, 'targetClass' => SubjectTopics::className(), 'targetAttribute' => ['topic_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'practice_id' => 'Practice ID',
            'topic_id' => 'Topic ID',
            'created_at' => 'Created At',
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        //if ($this->isRelationPopulated('score'))
            $fields['score'] = 'score';

        return $fields;
    }


    /**
     * Gets query for [[Practice]].
     *
     * @return \yii\db\ActiveQuery
     */

    public function getQuizSummary()
    {
        return $this->hasOne(QuizSummary::className(), ['topic_id' => 'id']);
    }

    public function getScore()
    {
        return $quizSummary = QuizSummaryDetails::find()->where(['topic_id'=>$this->id])->all();

        return $this->quizSummary;//$this->quizSummary->total_questions > 0 ? $this->quizSummary->correct / $this->quizSummary->total_questions * 100 : 0;
    }

    public function getPractice()
    {
        return $this->hasOne(Homeworks::className(), ['id' => 'practice_id']);
    }

    /**
     * Gets query for [[Topic]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTopic()
    {
        return $this->hasOne(SubjectTopics::className(), ['id' => 'topic_id']);
    }
}
