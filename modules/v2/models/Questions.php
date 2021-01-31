<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

/**
 * This is the model class for table "questions".
 *
 * @property int $id
 * @property int|null $teacher_id
 * @property int|null $homework_id
 * @property int $subject_id
 * @property string|null $class_id
 * @property int|null $school_id
 * @property string $question
 * @property string|null $option_a
 * @property string|null $option_b
 * @property string|null $option_c
 * @property string|null $option_d
 * @property string|null $option_e
 * @property string $answer Answer should either be A, B, C, D, or E FOr multiple options and 1=true/0=false for boolean true/false questions
 * @property string $type
 * @property int $topic_id
 * @property int $exam_type_id
 * @property string|null $image
 * @property string $difficulty
 * @property int $duration duration is in seconds
 * @property string|null $explanation
 * @property string|null $clue This give clue to the question
 * @property string $category
 * @property int|null $comprehension_id
 * @property int $status
 * @property string $created_at
 *
 * @property Comprehension $comprehension
 */
class Questions extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'questions';
    }

    public function rules()
    {
        return [
            [['teacher_id', 'homework_id', 'subject_id', 'school_id', 'topic_id', 'exam_type_id', 'duration', 'comprehension_id', 'status'], 'integer'],
            [['class_id', 'subject_id', 'question', 'answer', 'topic_id', 'difficulty', 'duration', 'option_a', 'option_b', 'option_c', 'option_d'], 'required', 'on' => 'create-multiple'],
            [['class_id', 'subject_id', 'question', 'answer', 'topic_id', 'difficulty', 'duration'], 'required', 'on' => 'create-bool'],
            [['answer'], 'integer', 'on' => 'create-bool'],
            [['question', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'type', 'difficulty', 'explanation', 'clue', 'category', 'image'], 'string'],
            [['created_at'], 'safe'],
            //[['answer'], 'string', 'max' => 1],
            [['comprehension_id'], 'exist', 'skipOnError' => true, 'targetClass' => Comprehension::className(), 'targetAttribute' => ['comprehension_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'teacher_id' => 'Teacher ID',
            'homework_id' => 'Homework ID',
            'subject_id' => 'Subject ID',
            'class_id' => 'Class ID',
            'school_id' => 'School ID',
            'question' => 'Question',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'option_e' => 'Option E',
            'answer' => 'Answer',
            'type' => 'Type',
            'topic_id' => 'Topic ID',
            'exam_type_id' => 'Exam Type ID',
            'image' => 'Image',
            'difficulty' => 'Difficulty',
            'duration' => 'Duration',
            'explanation' => 'Explanation',
            'clue' => 'Clue',
            'category' => 'Category',
            'comprehension_id' => 'Comprehension ID',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'question',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'option_e',
            'answer',
            'image',
            //'duration', //Had to add custom duration
            'duration' => 'customDuration',
            'difficulty',
            'type',
            'topic_id',
            'comprehension',
            'comprehension_id',
            'topic' => 'questionTopic',
            'owner' => 'questionOwner',
            'correct_students' => 'correctQuizSummaryDetails',
            'wrong_students' => 'wrongQuizSummaryDetails'
        ];
    }

    public function getCustomDuration()
    {
        if ($this->class_id >= 1 && $this->class_id <= 6 || $this->class_id > 12) {
            if ($this->difficulty == 'medium')
                $duration = 240;
            elseif ($this->difficulty == 'hard')
                $duration = 180;
            else
                $duration = 120;
        } else {
            if ($this->difficulty == 'medium')
                $duration = 75;
            elseif ($this->difficulty == 'hard')
                $duration = 90;
            else
                $duration = 60;
        }
        return $duration;
    }

    public function getCorrectQuizSummaryDetails()
    {
        return User::find()
            ->innerJoin('quiz_summary_details qsd', 'qsd.student_id = user.id AND qsd.question_id = ' . $this->id)
            ->where(['qsd.selected' => 'qsd.answer'])
            ->andWhere(['user.type' => SharedConstant::ACCOUNT_TYPE[3], 'qsd.homework_id' => Yii::$app->request->get('id')])
            ->all();
    }

    public function getWrongQuizSummaryDetails()
    {
        return User::find()
            ->innerJoin('quiz_summary qs', 'qs.student_id = user.id')
            ->leftJoin('quiz_summary_details qsd', 'qsd.student_id = user.id AND qsd.question_id = ' . $this->id)
            ->where(['OR',['!=','qsd.selected', 'qsd.answer'],['qsd.selected'=>null]])
            ->andWhere(['user.type' => SharedConstant::ACCOUNT_TYPE[3], 'qsd.homework_id' => Yii::$app->request->get('id'),'qs.homework_id' => Yii::$app->request->get('id')])
            ->all();
    }

    public function getComprehension()
    {
        return $this->hasOne(Comprehension::className(), ['id' => 'comprehension_id']);
    }

    public function getQuestionOwner()
    {
        return $this->teacher_id == Yii::$app->user->id ? 1 : 0;
    }

    public function getQuestionTopic()
    {
        $topic = SubjectTopics::findOne(['id' => $this->topic_id]);
        return !empty($topic) ? $topic->topic : null;
    }

    public static function find()
    {
        return parent::find()->andWhere(['<>', 'status', SharedConstant::STATUS_DELETED]);
    }

//    public static function getDb()
//    {
//        return Yii::$app->get('dblive');
//    }
}
