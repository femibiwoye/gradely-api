<?php

namespace app\modules\v2\models;

use app\modules\v2\school\models\PreferencesForm;
use Yii;

/**
 * This is the model class for table "school_topic".
 *
 * @property int $id
 * @property string $topic
 * @property int|null $topic_id
 * @property int $school_id
 * @property int $subject_id
 * @property int $class_id
 * @property int $curriculum_id
 * @property int|null $position
 * @property string $term
 * @property int|null $week
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Classes $class
 * @property ExamType $curriculum
 * @property Schools $school
 * @property Subjects $subject
 * @property SubjectTopics $topic0
 */
class SchoolTopic extends \yii\db\ActiveRecord
{

    public $learning;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_topic';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['topic', 'school_id', 'subject_id', 'class_id', 'curriculum_id', 'term'], 'required'],
            [['id', 'topic_id', 'school_id', 'subject_id', 'class_id', 'curriculum_id', 'position', 'week'], 'integer'],
            [['term'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['topic'], 'string', 'max' => 200],
            [['class_id'], 'exist', 'skipOnError' => true, 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
            [['curriculum_id'], 'exist', 'skipOnError' => true, 'targetClass' => ExamType::className(), 'targetAttribute' => ['curriculum_id' => 'id']],
            [['school_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
            [['subject_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']],
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
            'topic' => 'Topic',
            'topic_id' => 'Topic ID',
            'school_id' => 'School ID',
            'subject_id' => 'Subject ID',
            'class_id' => 'Class ID',
            'curriculum_id' => 'Curriculum ID',
            'position' => 'Position',
            'term' => 'Term',
            'week' => 'Week',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Class]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id']);
    }

    /**
     * Gets query for [[Curriculum]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCurriculum()
    {
        return $this->hasOne(ExamType::className(), ['id' => 'curriculum_id']);
    }

    /**
     * Gets query for [[School]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchool()
    {
        return $this->hasOne(Schools::className(), ['id' => 'school_id']);
    }

    /**
     * Gets query for [[Subject]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }

    /**
     * Gets query for [[SubjectTopic]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSubjectTopic()
    {
        return $this->hasOne(SubjectTopics::className(), ['id' => 'topic_id']);
    }

    public static function SchoolReplicateTopic($school, $curriculum)
    {
        if (SchoolTopic::find()->where(['school_id' => $school])->exists()) {
            return false;
        }
        $classes = Classes::find()->where(['school_id' => $school])->groupBy('global_class_id')->all();
        foreach ($classes as $class) {
            $topics = SubjectTopics::find()->where(['status' => 1, 'class_id' => $class->global_class_id])->orderBy(['class_id' => SORT_ASC, 'term' => SORT_ASC, 'week_number' => SORT_ASC])->all();
            foreach ($topics as $order => $topic) {
                $schoolModel = new SchoolTopic();
                $schoolModel->topic_id = $topic->id;
                $schoolModel->topic = $topic->topic;
                $schoolModel->school_id = $school;
                $schoolModel->subject_id = $topic->subject_id;
                $schoolModel->class_id = $class->id;
                $schoolModel->curriculum_id = $curriculum;
                $schoolModel->position = $order + 1;
                $schoolModel->term = $topic->term;
                $schoolModel->week = $topic->week_number;
                if (!$schoolModel->save()) {
                    print_r($schoolModel->errors);
                    die;
                }
            }
        }
        return true;
    }

    public function getLearningArea()
    {
        return $this->hasMany(LearningArea::className(), ['topic_id' => 'id'])->andWhere(['is_school' => 1]);
    }

}
