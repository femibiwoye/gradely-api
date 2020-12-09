<?php

namespace app\modules\v2\components;

use app\modules\v2\models\QuizSummary;
use app\modules\v2\models\UserProfile;
use yii\base\Model;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class StudentAnalytics extends Model
{
    public function Percentile($array, $percentile)
    {
        $percentile = min(100, max(0, $percentile));
        $array = array_values($array);
        sort($array);
        $index = ($percentile / 100) * (count($array) - 1);
        $fractionPart = $index - floor($index);
        $intPart = floor($index);

        $percentile = $array[$intPart];
        $percentile += ($fractionPart > 0) ? $fractionPart * ($array[$intPart + 1] - $array[$intPart]) : 0;
        return $percentile;
    }

    public function PercentileRank($values, $n)
    {
        $L = 0;
        $S = 0;
        $N = count($values);

        for ($i = 0; $i < count($values); $i++) {
            if ($values[$i] < $n) {
                $L += 1;
            } else if ($values[$i] === $n) {
                $S += 1;
            } else {

            }
        }

        $pct = ($L + (0.5 * $S)) / $N;

        return $pct;
    }

    public function ClassPosition($position, $percentile, $aggregate)
    {
        if ($aggregate >= $percentile) {
            $rank = 100 - $position;
        } else {
            $rank = $position;
        }
        return $rank;
    }

    /**
     * This function gets the aggregate of students or a student in a class of overall
     *
     * @param null $studentId
     * @param null $subject
     * @param null $term
     * @return array
     */
    public function StudentsClassAggregateScores($studentId = null, $subject = null, $term = null, $type, $state)
    {
        if (!empty($subject) || !empty($term) || !empty($studentId) || $state) {
            $model = QuizSummary::find();

            if (!empty($type))
                $model = $model->andWhere(['quiz_summary.type' => $type]);

            $model = $model->andWhere(['AND',
                !empty($subject) ? ['quiz_summary.subject_id' => $subject] : [],
                !empty($term) ? ['term' => $term] : [],
                !empty($studentId) ? ['quiz_summary.student_id' => $studentId] : []
            ]);


            if ($state) {
                $myState = UserProfile::findOne(['user_id' => $studentId]);
                $model = $model->innerJoin('user_profile', '`user_profile`.`user_id` = `quiz_summary`.`student_id`')
                    ->andWhere(['like', 'user_profile.state', !empty($myState->state) ? $myState->state : '']);
            }

        } else {
            $model = QuizSummary::find();
            if (!empty($type))
                $model = $model->where(['quiz_summary.type' => $type]);
        }

        $model = $model->innerJoin('homeworks', "homeworks.id = quiz_summary.homework_id AND homeworks.type = 'homework'")
            ->leftJoin('classes', 'classes.id = homeworks.class_id')
            ->andWhere(['classes.global_class_id' => Utility::getStudentClass(1, Utility::getParentChildID())]);


        $homeworkModel = clone $model;

        $finalHomework = $homeworkModel
            ->select([
                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(homework_questions.id))*100) as correctPercentage'),
                new Expression('COUNT(qsd.id) as attempt'),
                'COUNT(homework_questions.id) as questionCount',
                new Expression('SUM(case when qsd.selected = qsd.answer then 1 else 0 end) as correct'),
                'quiz_summary.student_id'
            ])
            ->innerJoin('quiz_summary_details qsd', "qsd.student_id = quiz_summary.student_id")
            ->andWhere(['submit' => 1])
            ->innerJoinWith(['homeworkQuestions'], false)
            ->groupBy('quiz_summary.student_id')
            ->orderBy('correctPercentage DESC')
            ->asArray()
            ->all();


//        $finalHomework = $homeworkModel
//            ->select([new Expression('round((SUM(distinct(correct))/COUNT(homework_questions.id))*100) as correctPercentage'),
//                'quiz_summary.student_id, COUNT(homework_questions.id) as questionCount, SUM(distinct(correct)) as correct'])
//            ->innerJoinWith(['homeworkQuestions'], false)
//            ->groupBy(['quiz_summary.student_id'])
//            ->orderBy('correctPercentage DESC')
//            ->andWhere(['submit' => 1])
//            ->asArray()
//            ->all();


//        if ($studentId) {
//            print_r($finalHomework);
//            die;
//        }

        foreach ($finalHomework as $key => $homework) {
            if (!empty($homework['correct'])) {
                $finalHomework[$key]['correct'] += $homework['correct'];
                $finalHomework[$key]['questionCount'] += $homework['questionCount'];
                $finalHomework[$key]['correctPercentage'] = round(($finalHomework[$key]['correct'] / $finalHomework[$key]['questionCount']) * 100);
            }
        }


        return $finalHomework;
    }

    public function Analytics($student, $subject = null, $term = null, $type = null, $state = false)
    {
        $percentileRank = 0;
        $classPosition = 0;
        $percentile = 0;
        $studentAggregate = 0;
        $studentOverallAndPosition = [0, 0];

        if (!empty($student)) {

            $classStudentsAggregate = $this->StudentsClassAggregateScores(null, $subject, $term, $type, $state);

            if (!empty($classStudentsAggregate)) {

                $studentAggregate = $this->StudentsClassAggregateScores($student->id, $subject, $term, $type, $state);

                $modelAggregate = $classStudentsAggregate;
                $classStudentsAggregate = ArrayHelper::getColumn($classStudentsAggregate, 'correctPercentage');
                $studentAggregate = ArrayHelper::getColumn($studentAggregate, 'correctPercentage');

                $studentIDs = ArrayHelper::getColumn($modelAggregate, 'student_id');
                $studentOverallAndPosition = [array_search($student->id, $studentIDs), count($studentIDs)];

                $studentAggregate = isset($studentAggregate[0]) ? $studentAggregate[0] : 0;
                $percentileRank = $this->PercentileRank($classStudentsAggregate, $studentAggregate) * 100;


                $percentile = $this->percentile($classStudentsAggregate, 50);
                $classPosition = $this->ClassPosition($percentileRank, $percentile, $studentAggregate);
            }
        }

        if ($myState = UserProfile::findOne(['user_id' => $student->id])) {
            $myState = $myState->state;
        } else {
            $myState = null;
        }

        $return = [
            'percentile' => round($percentile),
            'classPosition' => round($classPosition),
            'percentileRank' => round($percentileRank),
            'studentAggregate' => round($studentAggregate),
            'rankPosition' => $studentAggregate >= $percentile ? "Top" : "Bottom",
            'studentOverallAndPosition' => ($studentOverallAndPosition),
            'stateRanking' => $state && $myState,
            'stateName' => $myState
        ];

        return $return;
    }

    public function RankByHomework($studentID, $homeworkID)
    {

        $connection = \Yii::$app->getDb();
        $command = $connection->createCommand("SELECT t1.id, correct, homework_id, student_id,
(SELECT COUNT(*) FROM quiz_summary t2 WHERE t2.correct > t1.correct AND t2.homework_id = t1.homework_id) +1
AS rnk
FROM quiz_summary t1 WHERE homework_id = $homeworkID GROUP BY student_id order by rnk;");

        $result = $command->queryAll();
        //print_r(($result));

        $positionIndex = array_search($studentID, array_column($result, 'student_id'));
        return $result[$positionIndex]['rnk'];

    }

    public function HomeworkClassAverage($homeworkID)
    {
        $avg = QuizSummary::find()->select([new Expression('round(((SUM(correct)/SUM(total_questions)) * 100)/count(id)) as correctPercentage')])->where(['homework_id' => $homeworkID, 'type' => 'homework'])->asArray()->one();
        return isset($avg['correctPercentage']) ? $avg['correctPercentage'] : 0;
    }

    public function RankWithOrdinal($number)
    {
        $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
        if (($number % 100) >= 11 && ($number % 100) <= 13)
            return $number . 'th';
        else
            return $number . $ends[$number % 10];
    }

}
