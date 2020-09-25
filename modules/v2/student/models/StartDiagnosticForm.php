<?php

namespace app\modules\v2\student\models;

use app\modules\v2\components\Utility;
use app\modules\v2\models\Classes;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\models\Subjects;
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
class StartDiagnosticForm extends Model
{
    public $subject_id;

    public function rules()
    {
        return [
            [['subject_id'], 'required'],
            [['subject_id'], 'exist', 'targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']],
            ['subject_id', 'validateType'],
        ];
    }

    public function validateType()
    {
        if (!Subjects::find()->where(['id' => $this->subject_id, 'diagnostic' => 1])->exists()) {
            return $this->addError('subject_id', 'Subject not available for diagnostic');
        }

        if (QuizSummary::find()->where(['student_id' => Yii::$app->user->id, 'type' => 'diagnostic', 'submit' => 1])->exists()) {
            return $this->addError('subject_id', 'Diagnostic already taken');
        }

        return true;
    }

    public function initializeDiagnostic()
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {

            $globalClass = Utility::getStudentClass(SharedConstant::VALUE_ONE);
            if (empty($globalClass)) {
                return false;
            }

            $termWeek = Utility::getStudentTermWeek();

            return $topics = $this->diagnosticTopics($globalClass, $this->subject_id, $termWeek);


            $dbtransaction->commit();
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }

    }

    private function diagnosticTopics($globalClass, $subject, $termWeek)
    {

        $currentTerm = $termWeek['term'];
        $currentWeek = $termWeek['week'];
        switch (strtolower($currentTerm)) {
            case 'first':
                $terms = ['first', 'third'];
                $globalClass = [$globalClass, $globalClass - 1];
                break;
            case 'second':
                $terms = ['second', 'first'];
                break;
            case 'third':
                $terms = ['third', 'second'];
                break;
            default:
                $terms = ['first', 'second', 'third'];
                break;
        }

        $topics = [];
        $continue = false;


        foreach ($terms as $key => $term) {

            if (strtolower($currentTerm) != $term && $continue == true) {
                continue;
            }

            $topic = SubjectTopics::find()
                ->alias('s')
                ->innerJoin('questions', 'questions.topic_id = s.id')
                ->where(['s.term' => $term, 's.class_id' => $globalClass, 's.subject_id' => $subject])
                ->asArray()
                ->orderBy(['s.week_number' => SORT_DESC])
                ->groupBy('s.week_number');

            if ($key > 0) {
                $globalClass--;
            } else {
                $topic = $topic->andWhere(['<', 's.week_number', $currentWeek]);
            }

            $topic = $topic->all();

            foreach ($topic as $k => $row) {

                if (count($topics) >= 5) {
                    if ($key >= 1) {
                        $continue = true;
                        continue;
                    }
                }
                array_push($topics, $row);
            }
        }

        $topics = array_splice($topics, 0, 5);

        return $topics;
    }


}