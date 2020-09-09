<?php
namespace app\modules\v2\student\models;

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
    HomeworkQuestions
};

/**
 * Password reset request form
 */
class StartPracticeForm extends Model {
    public $topic_ids;
    public $type;
    private $questions_duration = SharedConstant::VALUE_ZERO;

    public function rules() {
        return [
            [['topic_ids', 'type'], 'required'],
            ['topic_ids', 'each', 'rule' => ['integer']],
            ['type', 'in', 'range' => [SharedConstant::MIX_TYPE_ARRAY, SharedConstant::SINGLE_TYPE_ARRAY]],
            ['type', 'validateType'],
        ];
    }

    public function validateType()
    {
        if (count($this->topic_ids) > SharedConstant::VALUE_ONE && $this->type == SharedConstant::MIX_TYPE_ARRAY) {
            return true;
        }

        return $this->addError('Type needs to be corrected');
    }

    public function initializePractice()
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$homework = $this->createHomework()) {
                return false;
            }

            if (!$this->createPracticeTopic($homework->id)) {
                return false;
            }

            if (!$this->createHomeworkQuestions($homework)) {
                return false;
            }


            $dbtransaction->commit();
        } catch (Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }

        return [
            'homework' => $homework,
            'duration' => $this->questions_duration,
            'practice_type' => $this->type,
        ];
    }

    public function createHomework()
    {
        $homework = new Homeworks;
        $homework->student_id = Yii::$app->user->id;
        $homework->subject_id = $this->getSubjectTopics($this->topic_ids[SharedConstant::VALUE_ZERO])->subject_id;
        $homework->title = $this->homeworkType;
        $homework->type = SharedConstant::HOMEWORK_TYPES[2];
        $homework->class_id = $this->getSubjectTopics($this->topic_ids[SharedConstant::VALUE_ZERO])->class_id;
        $homework->exam_type_id = $this->getSubjectTopics($this->topic_ids[SharedConstant::VALUE_ZERO])->exam_type_id;
        if (!$homework->save(false)) {
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
                            ->limit(SharedConstant::VALUE_THREE)
                            ->all();

            $this->addQuestions($homework, $questions);
        }

        return true;
    }

    private function addQuestions($homework, $questions)
    {
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