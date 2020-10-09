<?php

namespace app\modules\v2\student\models;

use app\modules\v2\models\Classes;
use app\modules\v2\models\Schools;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\{
    Homeworks,
    SubjectTopics,
    Questions,
    QuizSummaryDetails,
    PracticeTopics,
    HomeworkQuestions,
    Recommendations
};

/**
 * Password reset request form
 */
class StartPracticeForm extends Model
{
    public $topic_ids;
    public $type;
    public $practice_type;
    public $reference_id;
    public $reference_type;
    private $questions_duration = SharedConstant::VALUE_ZERO;

    public function rules()
    {
        return [
            [['topic_ids', 'type', 'practice_type'], 'required'],
            [['topic_ids', 'type', 'reference_type', 'reference_id', 'practice_type'], 'required', 'on' => 'create-practice'],
            ['topic_ids', 'each', 'rule' => ['integer']],
            ['type', 'in', 'range' => [SharedConstant::MIX_TYPE_ARRAY, SharedConstant::SINGLE_TYPE_ARRAY]],
            ['reference_type', 'in', 'range' => SharedConstant::REFERENCE_TYPE],
            ['type', 'validateType'],
        ];
    }

    public function validateType()
    {
        if (count($this->topic_ids) > SharedConstant::VALUE_ONE && $this->type == SharedConstant::MIX_TYPE_ARRAY) {
            return true;
        }
        if (count($this->topic_ids) == SharedConstant::VALUE_ONE && $this->type == SharedConstant::SINGLE_TYPE_ARRAY) {
            return true;
        }

        return $this->addError('type', 'Type needs to be corrected');
    }

    public function initializePractice($student_id = null, $teacher_id = null)
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$homework = $this->createHomework($student_id, $teacher_id)) {
                return false;
            }

            if (!$this->createPracticeTopic($homework->id)) {
                return false;
            }

            /*if (!$this->createHomeworkQuestions($homework)) {
                return false;
            }*/


            $dbtransaction->commit();
        } catch (Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }

        return array_merge(ArrayHelper::toArray($homework), [
            'duration' => $this->questions_duration,
            'practice_type' => $this->type,
        ]);
    }

    public function initializePracticeTemp($student_id = null, $teacher_id = null)
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {

            if (!$homework = $this->createHomework($student_id, $teacher_id)) {
                return false;
            }

            if (!$this->createPracticeTopic($homework->id)) {
                return false;
            }


            $dbtransaction->commit();
        } catch (Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }

        return array_merge(ArrayHelper::toArray($homework), [
            'duration' => $this->questions_duration,
            'practice_type' => $this->type,
        ]);
    }

    public function createHomework($student_id = null, $teacher_id = null)
    {
        $homework = new Homeworks(['scenario' => 'student-practice']);
        $homework->student_id = $student_id ? $student_id : Yii::$app->user->id;
        $homework->subject_id = $this->getSubjectTopics($this->topic_ids[SharedConstant::VALUE_ZERO])->subject_id;
        $homework->title = $this->homeworkType;
        $topic = $this->getSubjectTopics($this->topic_ids[SharedConstant::VALUE_ZERO]);
        $homework->class_id = $topic->class_id;
        if ($teacher_id) {
            $homework->teacher_id = $teacher_id;
            if ($this->reference_type == 'class') {
                $homework->school_id = Classes::findOne(['id' => $this->reference_id])->school_id;
                $homework->class_id = $this->reference_id;
            }
            if ($this->reference_type == 'homework') {
                $hwork = Homeworks::findOne(['id' => $this->reference_id]);
                $homework->school_id = $hwork->school_id;
                $homework->class_id = $hwork->class_id;
            }
        }

        if ($this->reference_type) {
            $homework->reference_type = $this->reference_type;
            $homework->reference_id = $this->reference_id;
        }

        $homework->type = $this->practice_type;

        $homework->exam_type_id = $topic->exam_type_id;
        if (!$homework->save()) {
            return false;
        }

        return $homework;
    }

    public function createPracticeTopic($homework_id)
    {
        foreach ($this->topic_ids as $topic_id) {
            $model = new PracticeTopics;
            $model->practice_id = $homework_id;
            $model->topic_id = $topic_id;
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }

    public function createHomeworkQuestions($homework)
    {
        //attempted_topics = Questions already attempted by the student.
        $attempted_topics = ArrayHelper::getColumn(QuizSummaryDetails::find()
            ->select(['question_id'])
            ->where(['student_id' => Yii::$app->user->id])
            ->groupBy('question_id')
            ->asArray()
            ->all(),
            'question_id');

        if ($this->type == SharedConstant::SINGLE_TYPE_ARRAY) {
            $questions = Questions::find()
                ->where([
                    'questions.subject_id' => $homework->subject_id,
                    'questions.topic_id' => $this->topic_ids[SharedConstant::VALUE_ZERO]
                ])
                ->andWhere(['NOT IN', 'id', $attempted_topics])
                ->limit(SharedConstant::VALUE_FIVE)
                ->orderBy('rand()')
                ->all();

            return $this->addQuestions($homework, $questions);

        }

        foreach ($this->topic_ids as $topic) {
            $questions = Questions::find()
                ->where([
                    'questions.subject_id' => $homework->subject_id,
                    'questions.topic_id' => $topic
                ])
                ->andWhere(['NOT IN', 'id', $attempted_topics])
                ->orderBy('rand()')
                ->limit(SharedConstant::VALUE_THREE)
                ->all();

            $this->addQuestions($homework, $questions);
        }

        return true;
    }

    private function addQuestions($homework, $questions)
    {
        if (count($questions) < 1)
            return false;

        foreach ($questions as $question) {
            $model = new HomeworkQuestions;
            $model->teacher_id = Yii::$app->user->id;
            $model->homework_id = $homework->id;
            $model->question_id = $question->id;
            $model->difficulty = $question->difficulty;
            $model->duration = $question->duration;
            if (!$model->save()) {
                return false;
            }

            $this->questions_duration = $this->questions_duration + $question->duration;
        }

        return true;
    }

    public function getSubjectTopics($topic_id)
    {
        return SubjectTopics::findOne(['id' => $topic_id]);
    }

    public function getHomeworkType()
    {
        $model = $this->getSubjectTopics($this->topic_ids[SharedConstant::VALUE_ZERO]);
        if ($this->type == SharedConstant::SINGLE_TYPE_ARRAY) {
            return $model->topic;
        }

        return $model->topic . (count($this->topic_ids) - SharedConstant::VALUE_ZERO);
    }

}
