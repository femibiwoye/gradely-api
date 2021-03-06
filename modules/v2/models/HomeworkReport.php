<?php

namespace app\modules\v2\models;

use app\modules\v2\components\Utility;
use Yii;
use app\modules\v2\components\SharedConstant;
use yii\helpers\ArrayHelper;

class HomeworkReport extends Homeworks
{
    private $homework_annoucements = [];
    private $sum_of_correct_answers;
    private $sum_of_attempts;
    private $total = SharedConstant::VALUE_ZERO;
    private $struggling_students = SharedConstant::VALUE_ZERO;
    private $average_students = SharedConstant::VALUE_ZERO;
    private $excellent_students = SharedConstant::VALUE_ZERO;


    public function fields()
    {
        return [
            'id',
            'title',
            'subject',
            'class_id',
            'school_id',
            'exam_type_id',
            'slug',
            'open_date',
            'close_date',
            'score',
            'status' => 'statusMessage', //this is used to be student to know if homework is open, expired or closed
            'expiry_status' => 'expiryStatus',
            'publish_status' => 'publishStatus',
            'topics',
            'attachments',
            'average',
            'completion',
            'questions' => 'homeworkQuestions',
            'homework_performance' => 'homeworkPerformance',
            'proctor',
        ];
    }

    public static function find()
    {
        return parent::find()->where(['homeworks.status' => 1]);
    }

    public function getHomeworkSummary()
    {
        $this->getScoreRange();
        return [
            'title' => $this->title,
            'tag' => $this->tag,
            'average' => $this->average,
            'completion_rate' => $this->completedRate,
            'expected_students' => $this->studentExpected,
            'submitted_students' => $this->studentsSubmitted,
            'struggling_students' => $this->struggling_students,
            'average_students' => $this->average_students,
            'excellence_students' => $this->excellent_students,
            'due_date' => $this->close_date

        ];
    }

    public function getCompletedRate()
    {
        $completed = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all();

        return count($this->getQuizSummaryRecord()->all()) > 0 ? count($completed) * 100 / count($this->getQuizSummaryRecord()->all()) : 0;
    }

    public function getStudentExpected()
    {
        return StudentSchool::find()
            ->where(['class_id' => $this->class_id, 'school_id' => $this->school_id, 'status' => 1])->count();
        //return count($this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all());
    }

    public function getStudentsSubmitted()
    {
        return (int)$this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->count();
    }

//    public function getStrugglingStudents()
//    {
//        $models = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all();
//        foreach ($models as $model) {
//            $marks = ($model->correct / $model->total_questions) * 100;
//            if ($marks < 50) {
//                $this->struggling_students = $this->struggling_students + 1;
//            }
//        }
//
//        return $this->struggling_students;
//    }
//
//    public function getAverageStudents()
//    {
//        $models = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all();
//        foreach ($models as $model) {
//            $marks = ($model->correct / $model->total_questions) * 100;
//            if ($marks >= 50 && $marks < 75) {
//                $this->average_students = $this->average_students + 1;
//            }
//        }
//
//        return $this->average_students;
//    }
//
//    public function getExcellentStudents()
//    {
//        $models = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all();
//        foreach ($models as $model) {
//            $marks = ($model->correct * $model->total_questions) * 100;
//            if ($marks >= 75) {
//                $this->excellent_students = $this->excellent_students + 1;
//            }
//        }
//
//        return $this->excellent_students;
//    }

    public function getScoreRange()
    {
        $models = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all();
        foreach ($models as $model) {
            $marks = $model->total_questions > 0 ? ($model->correct / $model->total_questions) * 100 : 0;
            if ($marks >= 75) {
                $this->excellent_students = $this->excellent_students + 1;
            }
            if ($marks >= 50 && $marks < 75) {
                $this->average_students = $this->average_students + 1;
            }
            if ($marks < 50) {
                $this->struggling_students = $this->struggling_students + 1;
            }
        }
    }

    public function getAverage()
    {
        //getting those records which are submitted
        $quizSummaries = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE]);
        if (!$quizSummaries->exists()) {
            return SharedConstant::VALUE_ZERO;
        }

        $totals = $quizSummaries->sum('total_questions');
        $sums = $quizSummaries->sum('correct');
        return $totals > 0 ? round(($sums / $totals) * 100) : 0;

//        foreach ($quizSummaries as $quizSummary) {
//            $this->sum_of_correct_answers = $this->sum_of_correct_answers + $quizSummary->correct;
//        }
//
//        return $quizSummaries[0]->total_questions > 0 ? (double)round(($this->sum_of_correct_answers / $quizSummaries[0]->total_questions) * 100) : 0;
    }

    public function getHomeworkQuestions()
    {
        return Questions::find()
            ->innerJoin('homework_questions', 'questions.id = homework_questions.question_id')
            ->where(['homework_questions.homework_id' => $this->id])
            ->all();
    }

    public function getHomeworkPerformance()
    {
        foreach ($this->homeworkQuestions as $homeworkQuestion) {
            return [
                'question_object' => $homeworkQuestion,
                'missed_student' => $this->getMissedStudents($homeworkQuestion->id),
                'correct_student' => $this->getCorrectStudents($homeworkQuestion->id),
            ];
        }
    }

    //work on the key of the arrays (start from here)

    public function getMissedStudents($question_id)
    {
        return User::find()
            ->innerJoin('quiz_summary_details', 'user.id = quiz_summary_details.student_id')
            ->where(['user.type' => SharedConstant::ACCOUNT_TYPE[3], 'quiz_summary_details.question_id' => $question_id])
            //->andWhere('quiz_summary_details.selected <> quiz_summary_details.answer') //to be removed eventually
            ->andWhere(['quiz_summary_details.selected' => 0])
            ->all();
    }

    public function getCorrectStudents($question_id)
    {
        return User::find()
            ->innerJoin('quiz_summary_details', 'user.id = quiz_summary_details.student_id')
            ->where(['user.type' => SharedConstant::ACCOUNT_TYPE[3], 'quiz_summary_details.question_id' => $question_id])
            //->andWhere('quiz_summary_details.selected = quiz_summary_details.answer') //To be removed eventually
            ->andWhere(['quiz_summary_details.selected' => 1])
            ->all();
    }

    public function getCompletion()
    {
        $quizSummaryRecords = $this->getQuizSummaryRecord()->all();
        if (!$quizSummaryRecords) {
            return SharedConstant::VALUE_ZERO;
        }

        foreach ($quizSummaryRecords as $quizSummaryRecord) {
            if ($quizSummaryRecord->submit = SharedConstant::VALUE_ONE) {
                $this->sum_of_attempts = $this->sum_of_attempts + SharedConstant::VALUE_ONE;
            }
        }

        return ($this->sum_of_attempts / count($quizSummaryRecords)) * 100 . '%';
    }

    public function getExpiryStatus()
    {
        if (time() > strtotime($this->close_date)) {
            return 'closed';
        } else {
            return 'open';
        }
    }

    public function getPublishStatus()
    {
        return $this->publish_status;
    }

    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }

    public function getQuizSummary()
    {

        return QuizSummary::find()->where(['student_id' => $this->student_id])
            ->orWhere(['teacher_id' => Yii::$app->user->id])
            ->andWhere(['subject_id' => $this->subject->id])
            ->andWhere(['homework_id' => $this->id])->one();

    }

    public function getScore()
    {

        if (!$this->quizSummary) {
            return null;
        }

        return $this->quizSummary->score;
    }

    public function getStatusMessage()
    {
        if (($this->score && $this->quizSummary->submit == SharedConstant::VALUE_ONE) && (strtotime($this->close_date) >= time() || strtotime($this->close_date) < time())) {
            return "Closed";
        } else if ((!$this->score || $this->quizSummary->submit != SharedConstant::VALUE_ONE) && strtotime($this->close_date) < time()) {
            return "Expired";
        } else {
            return "Open";
        }
    }

    public function getNewHomeworks()
    {
        if (Yii::$app->user->identity->type == 'teacher') {
            $condition = ['teacher_id' => Yii::$app->user->id];
        } elseif (Yii::$app->user->identity->type == 'school') {
            $classes = ArrayHelper::getColumn(Classes::find()
                ->where(['school_id' => Utility::getSchoolAccess()])->all(), 'id');
            $condition = ['class_id' => $classes];
        }
        $homeworks = parent::find()->where(['AND', $condition, ['publish_status' => 1, 'status' => 1, 'type' => 'homework']])
            ->andWhere(['>', 'open_date', date("Y-m-d")])
            ->orderBy(['open_date' => SORT_ASC])
            ->all();


        foreach ($homeworks as $homework) {
            if (strtotime($homework->open_date) <= time() + 604800 && strtotime($homework->open_date) >= time()) {
                array_push($this->homework_annoucements, [
                    'id' => $homework->id,
                    'type' => $homework->type,
                    'title' => $homework->title,
                    'date_time' => $homework->open_date,
                ]);
            }
        }

        return $this->homework_annoucements;
    }

    public function getQuizSummaryRecord()
    {
        return $this->hasMany(QuizSummary::className(), ['homework_id' => 'id'])
            ->innerJoin('student_school ss', 'ss.class_id = quiz_summary.class_id AND ss.student_id = quiz_summary.student_id');
    }

    public function getRestartHomework()
    {
        foreach ($this->quizSummaryRecord as $quizSummary) {
            if (!$quizSummary->delete()) {
                return false;
            }
        }

        return true;
    }

    public function getPracticeMaterials()
    {
        return $this->hasMany(PracticeMaterial::className(), ['practice_id' => 'id']);
    }

    public function getTopicsID()
    {
        return $this->hasMany(PracticeTopics::className(), ['practice_id' => 'id']);
    }

    public function getTopics()
    {
        return $this->hasMany(SubjectTopics::className(), ['id' => 'topic_id'])->via('topicsID');
    }

    public function getAttachments()
    {
        return $this->hasMany(PracticeMaterial::className(), ['practice_id' => 'id'])->andWhere(['type' => 'practice']);
    }

    public function getProctor()
    {
        $model = ProctorReport::find()
            ->where(['assessment_id' => $this->id])
            ->one();

        if (!$model) {
            return SharedConstant::VALUE_NULL;
        }

        return $model;
    }

}
