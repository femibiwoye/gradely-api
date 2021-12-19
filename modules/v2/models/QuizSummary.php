<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "quiz_summary".
 *
 * @property int $id
 * @property int $homework_id
 * @property int $subject_id
 * @property int $student_id
 * @property int|null $teacher_id
 * @property int $class_id
 * @property string $type it is either homework or catchup
 * @property int $total_questions
 * @property int|null $correct
 * @property int|null $failed
 * @property int|null $skipped
 * @property string $term
 * @property string $mode
 * @property string $created_at
 * @property string|null $submit_at
 * @property string|null $max_score
 * @property string $session
 * @property int $submit
 * @property int $computed
 * @property int $topic_id
 *
 * @property User $student
 * @property QuizSummaryDetails[] $quizSummaryDetails
 */
class QuizSummary extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    private $attempt_questions = [];

    public static function tableName()
    {
        return 'quiz_summary';
    }

    public function rules()
    {
        return [
            [['homework_id', 'subject_id', 'student_id', 'class_id', 'total_questions', 'term'], 'required'],
            [['homework_id', 'subject_id', 'student_id', 'teacher_id', 'class_id', 'total_questions', 'correct', 'failed', 'skipped', 'submit', 'topic_id', 'computed'], 'integer'],
            [['type', 'term', 'mode', 'session'], 'string'],
            [['created_at', 'teacher_id', 'max_score'], 'safe'],
            [['submit_at'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'homework_id' => 'Homework ID',
            'subject_id' => 'Subject ID',
            'student_id' => 'Student ID',
            'teacher_id' => 'Teacher ID',
            'class_id' => 'Class ID',
            'type' => 'Type',
            'total_questions' => 'Total Questions',
            'correct' => 'Correct',
            'failed' => 'Failed',
            'skipped' => 'Skipped',
            'term' => 'Term',
            'created_at' => 'Created At',
            'submit_at' => 'Submit At',
            'submit' => 'Submit',
            'topic_id' => 'Topic ID',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'homework_id',
            'subject_id',
            'student_id',
            'teacher_id',
            'class_id',
            'type',
            'submit',
            'created_at',
            'submit_at',
            'score',
        ];
//		return ['duration', 'total_questions'];
    }

    public function getAttempt()
    {
        foreach ($this->quizSummaryDetails as $quizSummaryDetail) {
            $attemptArray = [
                'question_id' => $quizSummaryDetail->question_id,
                'selected' => $quizSummaryDetail->selected,
            ];

            array_push($this->attempt_questions, $attemptArray);
        }

        return [
            'quiz_id' => $this->id,
            'attempts' => $this->attempt_questions,
        ];
    }

    public function getScore()
    {
        $total = count($this->homeworkQuestions);
        return $total > 0 ? round(($this->correct / $total) * 100) : 0;
    }

    public function getStudent()
    {
        return $this->hasOne(UserModel::className(), ['id' => 'student_id'])->select(['id', 'firstname', 'lastname', 'code', 'email', 'phone', 'image', 'type']);
    }

    public function getTeacherHomework()
    {
        return $this->hasOne(Homeworks::className(), ['id' => 'homework_id'])->andWhere(['homeworks.teacher_id' => Yii::$app->user->id]);
    }

    public function getChildHomework()
    {
        return $this->hasOne(Homeworks::className(), ['id' => 'homework_id']);
    }

    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }

    public function getHomeworkQuestions()
    {
        return $this->hasMany(HomeworkQuestions::className(), ['homework_id' => 'homework_id']);
    }

    public function getQuizSummaryDetails()
    {
        return $this->hasMany(QuizSummaryDetails::className(), ['quiz_id' => 'id']);
    }

    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->session = Yii::$app->params['activeSession'];
        }
        return parent::beforeSave($insert);
    }

}
