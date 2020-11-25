<?php

namespace app\modules\v2\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use app\modules\v2\components\Utility;
use yii\db\Expression;

class StudentTopicPerformance extends Model
{
    public $term, $class;
    private $topic_performance = array();
    private $direction;
    private $total_topic_score;
    public function rules()
    {
        return [
            ['class', 'integer'],
            ['term', 'string'],
        ];
    }

    public function getPerformance()
    {
        return [
            'score' => $this->getTotalObtainedScore(),
            'total' => $this->getTotalTopics(),
            'topics' => $this->getTopics(),
        ];
    }

    public function getTopics()
    {
        $topics = ArrayHelper::getColumn(
            SubjectTopics::find()
                    ->innerJoin('quiz_summary_details', 'quiz_summary_details.topic_id = subject_topics.id')
                    ->where([
                        'subject_topics.term' => $this->getTerm(),
                        'subject_topics.class_id' => $this->getClass(),
                        'quiz_summary_details.student_id' => $this->getStudent()
                    ])
                    ->all(),
            'subject_topics.id'
        );

        foreach ($topics as $topic) { 
            $topic_info = $this->getTopicInfo($topics);

            $topic_array = [
                'topic_id' => $topic_info['id'],
                'name' => $topic_info['topic'],
                'week_number' => $topic_info['week_number'],
                'term' => $topic_info['term'],
                'class' => $topic_info['class_id'],
                'score' => $this->getScore($topic),
                'total' => $this->getTotal(),
                'average' => $this->getAverage($topic),
                'improvement' => $this->getImprovement($topic),
                'direction' => $this->direction,
            ];

            array_push($this->topic_performance, $topic_array);
        }

        return $this->topic_performance;
    }

    private function getStudent()
    {
        return Yii::$app->user->id;
    }

    private function getTopicInfo($topic_id)
    {
        return SubjectTopics::find()
                    ->innerJoin('quiz_summary_details', 'quiz_summary_details.topic_id = subject_topics.id')
                    ->where([
                        'subject_topics.id' => $topic_id,
                    ])
                    ->asArray()
                    ->one();
        
    }

    private function getScore($topic)
    {
        $easy_attempts = $this->getAttemptedQuestions($topic)
                            ->andWhere(['questions.difficulty' => 'easy'])
                            ->asArray()
                            ->one();

        $medium_attempts = $this->getAttemptedQuestions($topic)
                            ->andWhere(['questions.difficulty' => 'medium'])
                            ->asArray()
                            ->one();

        $hard_attempts = $this->getAttemptedQuestions($topic)
                            ->andWhere(['questions.difficulty' => 'hard'])
                            ->asArray()
                            ->one();

        $hard_attempts_score = $this->getTotalScore($easy_attempts, 'hard');
        $medium_attempts_score = $this->getTotalScore($easy_attempts, 'medium');
        if ($hard_attempts_score == 30) {
            $easy_attempts_score = 40;
        } else {
            $easy_attempts_score = $this->getTotalScore($easy_attempts, 'easy');
        }

        $total_score = $easy_attempts_score + $medium_attempts_score + $hard_attempts_score;
            
        $value = $total_score == 100 ? Yii::$app->params['masteryPerTopicPerformance'] : ('0.' . $total_score) * Yii::$app->params['masteryPerTopicPerformance'];

        $this->total_topic_score = $this->total_topic_score + $value;

        return $value;
    }

    private function getTotalScore($attempts, $difficulty)
    {
        if ($difficulty == 'easy') {
            if ($attempts['total_questions'] >= Yii::$app->params['masteryQuestionCount']) {
                return 40;
            } else {
                $value = 0;
                for ($i = 0; $i < $attempts['total_questions']; $i++) { 
                    $value = $value + $this->getSingleQuestionScore('easy');
                }

                return $value;
            }
        } elseif ($difficulty == 'medium') {
            if ($attempts['total_questions'] >= Yii::$app->params['masteryQuestionCount']) {
                return 30;
            } else {
                $value = 0;
                for ($i = 0; $i < $attempts['total_questions']; $i++) { 
                    $value = $value + $this->getSingleQuestionScore('medium');
                }

                return $value;
            }
        } elseif ($difficulty == 'hard') {
            if ($attempts['total_questions'] >= Yii::$app->params['masteryQuestionCount']) {
                return 30;
            } else {
                $value = 0;
                for ($i = 0; $i < $attempts['total_questions']; $i++) { 
                    $value = $value + $this->getSingleQuestionScore('hard');
                }

                return $value;
            }
        } 
    }

    private function getSingleQuestionScore($difficulty)
    {
        if ($difficulty == 'easy') {
            return $this->getEasyPercentage() / Yii::$app->params['masteryQuestionCount'];
        } else if ($difficulty == 'medium') {
            return $this->getMediumPercentage() / Yii::$app->params['masteryQuestionCount'];
        } else {
            return $this->getHardPercentage() / Yii::$app->params['masteryQuestionCount'];
        }
    }

    private function getEasyPercentage()
    {
        return Yii::$app->params['masteryPerTopicPerformance'] * 0.4;
    }

    private function getHardPercentage()
    {
        return Yii::$app->params['masteryPerTopicPerformance'] * 0.3;
    }

    private function getMediumPercentage()
    {
        return Yii::$app->params['masteryPerTopicPerformance'] * 0.3;
    }

    private function getAttemptedQuestions($topic_id)
    {
        return QuizSummaryDetails::find()
                            ->select([
                                new Expression('round((SUM(case when quiz_summary_details.selected = quiz_summary_details.answer then 1 else 0 end)/COUNT(quiz_summary_details.id))*100) as score'),
                                new Expression('SUM(case when quiz_summary_details.selected = quiz_summary_details.answer then 1 else 0 end) as total_questions')
                            ])
                            ->innerJoin('questions', 'quiz_summary_details.question_id = questions.id')
                            ->where([
                                'quiz_summary_details.topic_id' => $topic_id
                            ]);
    }

    private function getTotal()
    {
        return Yii::$app->params['masteryPerTopicPerformance'];
    }

    private function getAverage($topic_id)
    {
        $attempts = QuizSummaryDetails::find()
                            ->select([
                                new Expression('COUNT(quiz_summary_details.question_id) as total_attempts'),
                                new Expression('SUM(case when quiz_summary_details.selected = quiz_summary_details.answer then 1 else 0 end) as total_correct_questions')
                            ])
                            ->where([
                                'quiz_summary_details.topic_id' => $topic_id,
                                'quiz_summary_details.student_id' => $this->getStudent()
                            ])
                            ->asArray()
                            ->one();

        return $attempts['total_correct_questions'] / ($attempts['total_attempts'] == 0 ? 1 : $attempts['total_attempts']);
    }

    private function getImprovement($topic)
    {
        $last_attempts = QuizSummary::find()
                            ->leftJoin('quiz_summary_details', 'quiz_summary_details.quiz_id = quiz_summary.id')
                            ->where([
                                'quiz_summary_details.topic_id' => $topic,
                                'quiz_summary.class_id' => $this->class,
                                'quiz_summary.submit' => 1
                            ])
                            ->limit(2)
                            ->orderBy(['submit_at' => SORT_DESC])
                            ->all();

        $value = $this->getImprovemedPercentage($last_attempts);

        return $value;
    }

    private function getImprovemedPercentage($attempts)
    {
        if (count($attempts) == 1) {
            return;
        } else {
            $value = $this->getPercentage($attempts[0]->correct, $attempts[0]->total_questions) - $this->getPercentage($attempts[1]->correct, $attempts[1]->total_questions);

            if ($value < 0) {
                $this->direction = 'down';
            } else {
                $this->direction = 'up';
            }

            return $value;
        }
    }

    private function getPercentage($attempted, $total)
    {
        return ($attempted * 100) / $total;
    }

    private function getTotalObtainedScore()
    {
        return $this->total_topic_score;
    }

    private function getTotalTopics()
    {
        $total = count(ArrayHelper::getColumn(
            SubjectTopics::find()
                    ->innerJoin('quiz_summary_details', 'quiz_summary_details.topic_id = subject_topics.id')
                    ->where([
                        'subject_topics.term' => $this->getTerm(),
                        'subject_topics.class_id' => $this->getClass(),
                        'quiz_summary_details.student_id' => $this->getStudent()
                    ])
                    ->all(),
            'subject_topics.id'
        ));

        return $total * $this->getTotal();
    }

    private function getClass()
    {
        if (empty($this->class)) {
            $class = StudentSchool::find()
                    ->where([
                        'student_id' => Yii::$app->user->id
                    ])
                    ->asArray()
                    ->one();

            return $class['class_id'];

        } else {
            return $this->class;
        }
    }

    private function getTerm()
    {
        if (empty($this->term)) {
            return Utility::getStudentTermWeek()['term'];
        } else {
            return $this->term;
        }
    }
    
}