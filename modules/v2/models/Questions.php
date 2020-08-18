<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

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
            [['subject_id', 'question', 'answer', 'topic_id', 'exam_type_id', 'difficulty', 'duration'], 'required'],
            [['question', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'type', 'difficulty', 'explanation', 'clue', 'category'], 'string'],
            [['created_at'], 'safe'],
            [['class_id'], 'string', 'max' => 15],
            [['answer'], 'string', 'max' => 1],
            [['image'], 'string', 'max' => 200],
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
            'type',
            'correct_students' => 'correctQuizSummaryDetails',
            'wrong_students' => 'wrongQuizSummaryDetails'
        ];
    }

    public function getCorrectQuizSummaryDetails()
    {
        return User::find()
            ->innerJoin('quiz_summary_details qsd', 'qsd.student_id = user.id AND qsd.question_id = ' . $this->id)
            ->where('qsd.selected = qsd.answer')
            ->andWhere(['user.type' => SharedConstant::ACCOUNT_TYPE[3], 'qsd.homework_id' => Yii::$app->request->get('id')])
            ->all();
    }

    public function getWrongQuizSummaryDetails()
    {
        return User::find()
            ->innerJoin('quiz_summary_details qsd', 'qsd.student_id = user.id AND qsd.question_id = ' . $this->id)
            ->where('qsd.selected != qsd.answer')
            ->andWhere(['user.type' => SharedConstant::ACCOUNT_TYPE[3], 'qsd.homework_id' => Yii::$app->request->get('id')])
            ->all();
    }

    public function getComprehension()
    {
        return $this->hasOne(Comprehension::className(), ['id' => 'comprehension_id']);
    }

    public static function find()
    {
        return parent::find()->andWhere(['<>', 'status', SharedConstant::STATUS_DELETED]);
    }
}
