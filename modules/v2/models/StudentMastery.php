<?php

namespace app\modules\v2\models;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use Yii;
use yii\base\Model;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Password reset request form
 */
class StudentMastery extends Model
{
    public $student_id;
    public $term;
    public $class;
    public $subject;
    public $mode;
    private $studentDifficultyValue;
    public $exam;

    public function rules()
    {
        return [
            [['class', 'subject', 'exam'], 'integer'],
            [['term', 'mode'], 'string'],
            ['term', 'in', 'range' => ['first', 'second', 'third']]
        ];
    }


    /**
     * Initialize values.
     */
    private function getUpdateInitValues()
    {
        $this->studentDifficultyValue = [
            'easy' => 40 * Yii::$app->params['masteryPerTopicUnit'],
            'medium' => 30 * Yii::$app->params['masteryPerTopicUnit'],
            'hard' => 30 * Yii::$app->params['masteryPerTopicUnit']
        ];
    }

    public function getPerformance()
    {
        $this->getUpdateInitValues();
        return array_merge([
            'total' => $this->getTotalTopics(),
            'singleTotal' => $this->getSinglePercentageValue(),
        ], $this->getTopicDetails());
    }

    public function getPerformanceSummary()
    {
        $this->getUpdateInitValues();
        return [
            'total' => $this->getTotalTopics(),
            'singleTotal' => $this->getSinglePercentageValue(),
            'score' => $this->getTopicDetails(true)
        ];
    }

    /**
     * Return topics lists with details and summed correct value
     * @return array
     */
    private function getTopicDetails($examMode = false)
    {
        $topics = [];
        foreach ($this->getAllAccessibleTopics() as $topic) {
            $topic_info = $this->getScorePerTopic($topic->id);
            $topic_array = [
                'topic_id' => $topic->id,
                'name' => $topic->topic,
                'week' => $topic->week_number,
                'term' => $topic->term,
                'class' => $topic->class_id,
                'performance' => $topic_info,
            ];

            $topics[] = $topic_array;
        }

//        $score = array_sum(ArrayHelper::getColumn(ArrayHelper::getColumn($topics, 'performance'), 'singleScore'));
        $score = array_sum(ArrayHelper::getColumn(ArrayHelper::getColumn($topics, 'performance'), 'sharedScore'));
        return ($examMode) ? $score : ['score' => $score, 'topics' => $topics];
    }

    /**
     * Your metrics in a single topic
     * @param $topic
     * @return array
     */
    private function getScorePerTopic($topic)
    {
        $easy_attempt = $this->getAttemptedQuestions($topic, 'easy');
        $medium_attempt = $this->getAttemptedQuestions($topic, 'medium');
        $hard_attempt = $this->getAttemptedQuestions($topic, 'hard');

        $attempts = ['easy' => $easy_attempt, 'medium' => $medium_attempt, 'hard' => $hard_attempt];

        $attemptEasy = $this->getTotalScore($attempts, 'easy');
        $attemptMedium = $this->getTotalScore($attempts, 'medium');
        $attemptHard = $this->getTotalScore($attempts, 'hard');
        if ($attemptHard['singlePortion'] >= $this->studentDifficultyValue['hard']) {
            $attemptEasy = $this->getTopicCompletedScore('easy');
        }

        $summedSharedScore = round(array_sum([$attemptEasy['sharedPortion'], $attemptMedium['sharedPortion'], $attemptHard['sharedPortion']]));
        $summedSingleScore = round(array_sum([$attemptEasy['singlePortion'], $attemptMedium['singlePortion'], $attemptHard['singlePortion']]));

        return [
            'sharedScore' => $summedSharedScore,
            'singleScore' => $summedSingleScore,
            //'improvement' => $this->getLastTwoAttempt($topic),
            'details' => [
                'easy' => array_merge($attemptEasy, $easy_attempt),
                'medium' => array_merge($attemptMedium, $medium_attempt),
                'hard' => array_merge($attemptHard, $hard_attempt)
            ]
        ];
    }

    /**
     * What you score using 100%/per difficulty calculation and 40|30|30 calculation
     * @param $attempt
     * @param $difficulty
     * @return array
     */
    private function getTotalScore($attempt, $difficulty)
    {
        $correct = !empty($attempt[$difficulty]['correct']) ? $attempt[$difficulty]['correct'] : 0;
        if ($correct >= Yii::$app->params['masteryQuestionCount']) {
            return $this->getTopicCompletedScore($difficulty);
        }
        $singlePortion = ($correct / Yii::$app->params['masteryQuestionCount']) * $this->getSinglePercentageValue();
        $sharedPortion = ($correct / Yii::$app->params['masteryQuestionCount']) * $this->studentDifficultyValue[$difficulty];

        return ['sharedPortion' => $sharedPortion, 'singlePortion' => $singlePortion];
    }

    /**
     * To get the last two assessment and performance different within the last two attempt
     *
     * @param $topic
     * @return array
     */
    private function getLastTwoAttempt($topic)
    {
        $last_attempts = QuizSummaryDetails::find()
            ->select([
                new Expression('SUM(case when quiz_summary_details.selected = quiz_summary_details.answer then 1 else 0 end) as correct'),
                new Expression('COUNT(quiz_summary_details.id) as attempt'),
                new Expression("SUM(case when quiz_summary_details.selected = quiz_summary_details.answer then 1 else 0 end)/COUNT(quiz_summary_details.id)*100 as score"),
            ])
            ->leftJoin('quiz_summary', 'quiz_summary_details.quiz_id = quiz_summary.id')
            ->where([
                'quiz_summary_details.topic_id' => $topic,
                'quiz_summary.submit' => 1,
                'quiz_summary_details.student_id' => $this->student_id,
                'quiz_summary.student_id' => $this->student_id
            ])
            ->groupBy('quiz_summary_details.quiz_id')
            ->orderBy(['submit_at' => SORT_DESC])
            ->limit(2)
            ->asArray()
            ->all();

        $value = $this->getImprovementPercentage($last_attempts);

        return $value;
    }

    /**
     * Improvement difference within the last tw attempt
     * @param $attempts
     * @return array
     */
    private function getImprovementPercentage($attempts)
    {
        $direction = null;
        $improvement = null;
        if (count($attempts) <= 1) {
            $direction = null;
            $improvement = null;
        } else {
            $improvement = $attempts[0]['correct'] - $attempts[1]['correct'];
            if ($improvement > 0) {
                $direction = 'up';
            } else {
                $direction = 'down';
            }

            return ['improvement' => $improvement, 'direction' => $direction];
        }
    }


    /**
     * The highest value when you scored 100% of specified difficulty
     * @param $difficulty
     * @return array
     */
    private function getTopicCompletedScore($difficulty)
    {
        return ['sharedPortion' => $this->studentDifficultyValue[$difficulty], 'singlePortion' => $this->getSinglePercentageValue()];
    }

    /**
     * This gives you the correct, attempt and score of a student per topic.
     * @param $topic_id
     * @param $difficulty
     * @return QuizSummaryDetails|array|\yii\db\ActiveRecord|null
     */
    private function getAttemptedQuestions($topic_id, $difficulty)
    {

        $masteryPerTopicPerformance = $this->getSinglePercentageValue();

        $model = QuizSummaryDetails::find()
            ->select([
                new Expression('SUM(case when quiz_summary_details.selected = quiz_summary_details.answer then 1 else 0 end) as correct'),
                new Expression('COUNT(quiz_summary_details.id) as attempt'),
                new Expression("(SUM(case when quiz_summary_details.selected = quiz_summary_details.answer then 1 else 0 end)/COUNT(quiz_summary_details.id))*{$masteryPerTopicPerformance} as score"),
            ]);
        if($this->mode == 'exam'){
            $model = $model
                ->innerJoin('quiz_summary qs',"qs.id = quiz_summary_details.quiz_id AND qs.mode = 'exam'")
                ->innerJoin('questions', "questions.id = quiz_summary_details.question_id AND questions.category = 'exam'");
        }else {
            $model = $model
                ->innerJoin('quiz_summary qs',"qs.id = quiz_summary_details.quiz_id AND qs.mode != 'exam'")
                ->innerJoin('questions', "questions.id = quiz_summary_details.question_id AND questions.category != 'exam'");
        }
            $model = $model->where([
                'quiz_summary_details.topic_id' => $topic_id,
                'quiz_summary_details.student_id' => $this->student_id,
                'questions.difficulty' => $difficulty
            ])->asArray()
            ->one();

        return $model;
    }

    /**
     * Multiply the number of topics to be taken in that term with our multiplying figure. e.g
     * @return float|int
     */
    private function getTotalTopics()
    {
        $total = count($this->getAllAccessibleTopics());

        return $total * $this->getSinglePercentageValue();
    }

    /**
     * Multiply the number of topics to be taken in that term with our multiplying figure. e.g
     * @return float|int
     */
    private function getAllAccessibleTopics()
    {
        $mode = Utility::getChildMode($this->student_id);
        $topics = SubjectTopics::find()
            ->select(['subject_topics.id', 'topic', 'subject_topics.term', 'subject_topics.subject_id', 'week_number', 'subject_topics.class_id']);
        if ($mode == SharedConstant::EXAM_MODES[1]) {
            $topics = $topics
                ->innerJoin('questions', "questions.topic_id = subject_topics.id AND questions.category = '$mode' AND questions.exam_type_id = $this->exam")
                ->where([
                    'subject_topics.subject_id' => $this->subject
                ]);
        } else {
            $topics = $topics->where([
                'term' => $this->getTerm(),
                'subject_topics.class_id' => $this->getClass(),
                'status' => 1,
                'subject_id' => $this->subject,
                'school_id' => null,
            ]);
        }
        $topics = $topics->all();

        return $topics;
    }

    /**
     * By default, unit is 1, meaning 100% score is 100.
     * If the value changed to 2, it means 100% of a score becomes 200
     * @return mixed
     */
    private function getSingleUnitValue()
    {
        return Yii::$app->params['masteryPerTopicUnit'];
    }

    /**
     * This is 100% value.
     * if unit increase, from 1 to 2, this will retur 200
     * @return float|int
     */
    private function getSinglePercentageValue()
    {
        return $this->getSingleUnitValue() * Yii::$app->params['masteryPerTopicPerformance'];
    }

    private function getClass()
    {
        if (empty($this->class)) {
            return $class = Utility::ParentStudentChildClass($this->student_id, 1);
        } else {
            return $this->class;
        }
    }

    private function getTerm()
    {
        if (empty($this->term)) {
            return Utility::getStudentTermWeek('term', $this->student_id);
        }
        return $this->term;
    }
}
