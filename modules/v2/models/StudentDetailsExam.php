<?php

namespace app\modules\v2\models;

use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\StudentAnalytics;
use app\modules\v2\components\Utility;
use Yii;
use app\modules\v2\components\SharedConstant;
use yii\base\Model;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Password reset request form
 */
class StudentDetailsExam extends User
{
    private $hard_questions = SharedConstant::VALUE_ZERO;
    private $medium_questions = SharedConstant::VALUE_ZERO;
    private $easy_questions = SharedConstant::VALUE_ZERO;
    private $easy_score = SharedConstant::VALUE_ZERO;
    private $medium_score = SharedConstant::VALUE_ZERO;
    private $hard_score = SharedConstant::VALUE_ZERO;
    private $score = SharedConstant::VALUE_ZERO;
    private $improvement = SharedConstant::VALUE_ZERO;
    private $direction;

    public function fields()
    {
        return [
            'id',
            'firstname',
            'lastname',
            'code',
            'image' => 'imageUrl',
            'email',
            'phone',
            'type',
            'class_name' => 'className',
            'topics' => 'topicBreakdown',
            'selectedSubject' => 'selectedSubject',
            'selectedExam' => 'selectedExam',
            'performance' => 'performance',
            'mastery',
            'studyTime',
            'gradeScore',
            'leaderBoard'
        ];
    }

    public function getImageUrl()
    {
        if (empty($this->image))
            $image = null;
        elseif (strpos($this->image, 'http') !== false)
            $image = $this->image;
        else {
            $image = Yii::$app->params['baseURl'] . '/images/users/' . $this->image;
        }
        return $image;
    }

    public function getUser()
    {
        return $this;
    }

    public function getClassName()
    {
        $class = Classes::find()
            ->select(['classes.class_name'])
            ->innerJoin('student_school ss', 'ss.class_id = classes.id')
            ->where(['ss.student_id' => $this->id])
            ->one();
        return isset($class->class_name) ? $class->class_name : null;
    }

    public function getSummary()
    {
        return ['positions' => $this->getStatistics(), 'performance' => $this->getPerformance()];
    }

    public function getTopicBreakdown()
    {
        $topicBreakdown = new StudentDetails();
        $userType = Yii::$app->user->identity->type;
        return $topicBreakdown->getTopicBreakdownModel($this->id, $userType);
    }

    public function getQuestion($question_id)
    {
        $model = Questions::findOne(['id' => $question_id]);
        return isset($model->difficulty) ? $model->difficulty : SharedConstant::QUESTION_DIFFICULTY[2];
    }

    public function getQuestionsDifficulty($summary, $topic)
    {
        $this->getRecentAttempts($summary, $topic);
        $correctAttempts = $summary->andWhere(['=', 'selected', new Expression('`answer`')])->all();
        foreach ($correctAttempts as $correctAttempt) {
            if ($this->getQuestion($correctAttempt->question_id) == SharedConstant::QUESTION_DIFFICULTY[0]) {
                $this->hard_questions = $this->hard_questions + SharedConstant::VALUE_ONE;
            } elseif ($this->getQuestion($correctAttempt->question_id) == SharedConstant::QUESTION_DIFFICULTY[1]) {
                $this->medium_questions = $this->medium_questions + SharedConstant::VALUE_ONE;
            } elseif ($this->getQuestion($correctAttempt->question_id) == SharedConstant::QUESTION_DIFFICULTY[2]) {
                $this->easy_questions = $this->easy_questions + SharedConstant::VALUE_ONE;
            }
        }

        $this->easy_score = ((($this->easy_questions / 6) * 100) * 40) / 100;
        $this->medium_score = ((($this->medium_questions / 6) * 100) * 30) / 100;
        $this->hard_score = ((($this->hard_questions / 6) * 100) * 30) / 100;
        $this->score = round($this->easy_score + $this->medium_score + $this->hard_score);
    }

    public function getRecentAttempts($summary, $topic)
    {
        $easy_questions = SharedConstant::VALUE_ZERO;
        $medium_questions = SharedConstant::VALUE_ZERO;
        $hard_questions = SharedConstant::VALUE_ZERO;
        $score = [];
        $recent_attempts = $summary
            ->where(['quiz_summary_details.student_id' => $this->id, 'quiz_summary_details.topic_id' => $topic])
            //->groupBy('quiz_id')
            ->orderBy(['quiz_summary_details.topic_id' => SORT_DESC])
            ->limit(2);

        foreach ($recent_attempts->all() as $recent_attempt) {
            if ($recent_attempt->selected != $recent_attempt->answer) {
                continue;
            }

            if ($this->getQuestion($recent_attempt->question_id) == SharedConstant::QUESTION_DIFFICULTY[0]) {
                $hard_questions = SharedConstant::VALUE_ONE;
            } elseif ($this->getQuestion($recent_attempt->question_id) == SharedConstant::QUESTION_DIFFICULTY[1]) {
                $medium_questions = SharedConstant::VALUE_ONE;
            } elseif ($this->getQuestion($recent_attempt->question_id) == SharedConstant::QUESTION_DIFFICULTY[2]) {
                $easy_questions = SharedConstant::VALUE_ONE;
            }

            $easy_score = ((($easy_questions / 6) * 100) * 40) / 100;
            $medium_score = ((($medium_questions / 6) * 100) * 30) / 100;
            $hard_score = ((($hard_questions / 6) * 100) * 30) / 100;

            array_push($score, ($easy_score + $medium_score + $hard_score));
        }

        if (sizeof($score) > SharedConstant::VALUE_ONE) {
            if ($score[1] > $score[0]) {
                $this->direction = 'up';
                $difference = $score[1] - $score[0];
                $this->improvement = ceil(($difference * 10) / 100);
            } else {
                $this->direction = 'down';
                $difference = $score[1] - $score[0];
                $this->improvement = ceil(($difference * 10) / 100);
            }
        } else {
            $this->direction = null;
            $this->improvement = null;
        }
        return true;
    }

    public function checkStudentInTeacherClass()
    {
        if (Yii::$app->user->identity->type == 'teacher') {
            $teacher_classes = TeacherClass::find()->where(['teacher_id' => Yii::$app->user->id, 'status' => 1])->all();
            foreach ($teacher_classes as $teacher_class) {
                if (StudentSchool::find()->where(['class_id' => $teacher_class->class_id])->andWhere(['student_id' => $this->id])->exists()) {
                    return true;
                }
            }
        } elseif (Yii::$app->user->identity->type == 'school' && StudentSchool::find()->where(['student_id' => $this->id, 'school_id' => Utility::getSchoolAccess(Yii::$app->user->id)])->exists()) {
            return true;
        }

        return false;
    }

    public function getPerformance()
    {

        $topicBreakdown = new StudentDetails();
        return $topicBreakdown->getPerformanceModel($this->id);


//        $excellence = [];
//        $averages = [];
//        $struggling = [];
//
//        $activeTopics = QuizSummaryDetails::find()->select(['qsd.topic_id'])
//            ->alias('qsd')
//            ->where(['qsd.student_id' => $this->id]);
//
//        if (in_array(Yii::$app->user->identity->type, SharedConstant::EXAM_MODE_USER_TYPE)) {
//            $activeTopics = $activeTopics
//                ->leftJoin('quiz_summary qz','qz.id = qsd.quiz_id')
//                ->andWhere(['qz.mode' => Utility::getChildMode($this->id)]);
//        }
//
//        if (isset($_GET['term'])) {
//            $term = $_GET['term'];
//            $activeTopics = $activeTopics->innerJoin('quiz_summary qs', "qs.id = qsd.quiz_id AND qs.term ='$term'");
//        }
//
//        $activeTopics = ArrayHelper::getColumn($activeTopics->groupBy('qsd.topic_id')->all(), 'topic_id');
//
//        if (!empty($activeTopics)) {
//            if (isset($_GET['subject']) && !empty($_GET['subject'])) {
//                $selectedSubject = Subjects::find()->where(['status' => 1]);
//                $selectedSubject = $selectedSubject->andWhere(['id' => $_GET['subject']]);
//                $selectedSubject = ArrayHelper::getColumn($selectedSubject->all(), 'id');
//            } else {
////                $attemptedSubjects = QuizSummary::find()->where(['student_id' => $this->id])->groupBy('subject_id')->all();
////                $selectedSubject = ArrayHelper::getColumn($attemptedSubjects, 'subject_id');
//                $selectedSubject = isset($this->getSelectedSubject()['id']) ? $this->getSelectedSubject()['id'] : null;
//            }
//
//            $topics = SubjectTopics::find()
//                ->where([
//                    'subject_id' => $selectedSubject,
//                    'id' => $activeTopics
//                ])
//                ->all();
//
//            if (!empty($topics)) {
//                foreach ($topics as $data) {
//                    if ($data->getResult($this->id, $data->id) >= 75) {
//                        $excellence[] = $this->topicPerformanceMini($data);
//                    } elseif ($data->getResult($this->id, $data->id) >= 50 && $data->getResult($this->id, $data->id) < 75) {
//                        $averages[] = $this->topicPerformanceMini($data);
//                    } elseif ($data->getResult($this->id, $data->id) < 50) {
//                        $struggling[] = $this->topicPerformanceMini($data);
//                    }
//                }
//            }
//        }
//
//        $excellence = array_slice($excellence, 0, 3);
//        $averages = array_slice($averages, 0, 3);
//        $struggling = array_slice($struggling, 0, 3);
//
//        return [
//            'excellence' => $excellence,
//            'average' => $averages,
//            'struggling' => $struggling
//        ];

    }

    public function getClassSubjects($subject_id = null)
    {

        $studentID = $this->id;

        $subjectIDS = ArrayHelper::getColumn(QuizSummary::find()->select(['subject_id'])->where(['student_id' => $studentID, 'submit' => 1,'mode'=>'exam'])->groupBy('subject_id')->all(), 'subject_id');
        $subjects = Subjects::find()
            ->alias('s')
            ->select([
                's.id',
                's.slug',
                's.name',
                //'s.description',
                //'s.image',
            ])
            ->leftJoin('student_school ss', "ss.student_id = $studentID AND ss.status = 1")
            ->leftJoin('class_subjects cs', 'cs.class_id = ss.class_id AND cs.school_id = ss.school_id AND cs.subject_id = s.id AND cs.status = 1')
            ->where(['s.status' => 1])->orWhere(['s.id' => $subjectIDS]);

        if (!empty($subject_id))
            $subjects = $subjects->andWhere(['s.id' => $subject_id]);

        $subjects = $subjects->all();

        return $subjects;
    }

    public function getSelectedSubject()
    {
        if ($subject_id = Yii::$app->request->get('subject')) {
            $subject = $this->getClassSubjects($subject_id)[0];
        } else {
            $subject = isset($this->getClassSubjects()[0]) ? $this->getClassSubjects()[0] : null;
        }
        return $subject;
    }

    public function getSelectedExam()
    {
        $studentID = Utility::getParentChildID();
        if ($examID = Yii::$app->request->get('exam_id')) {
            $model = ExamType::find()
                ->select(['id', 'name', 'title', 'slug'])
                ->where(['id' => $examID])
                ->one();
        } else {
            $model = ExamType::find()
                ->select(['id', 'name', 'title', 'slug'])
                ->where(['id' => Utility::StudentExamSubjectID($studentID, 'exam_id')])
                ->one();
        }
        return $model;
    }

    public function getMastery()
    {
        $model = new StudentMastery();
        $model->student_id = $this->id;
        $model->term = Yii::$app->request->get('term');
        $model->subject = isset($this->getSelectedSubject()['id']) ? $this->getSelectedSubject()['id'] : null;
        $model->mode = Utility::getChildMode($this->id);
        if($model->mode == 'exam') {
            $model->exam = isset($this->getSelectedExam()['id']) ? $this->getSelectedExam()['id'] : null;
        }
        if (!$model->validate()) {
            return ['total' => 0, 'score' => 0, 'percentage' => 0];
        }
        return $model->getPerformanceSummary();
    }

    public function getStudyTime()
    {
        $mode = Utility::getChildMode($this->id);
        $model = QuizSummary::find()->select([
            new Expression('SUM(TIME_TO_SEC(TIMEDIFF(created_at,submit_at))) exam_time')
        ])->where(['student_id' => $this->id, 'mode' => $mode, 'submit_at' => 1])->asArray()->one();

        return $model['exam_time'];
    }

    public function getGradeScore()
    {
        $score = $this->getExamScores();
        return ['indicator' => Utility::GradeIndicator($score), 'score' => $score];
    }

    public function getCountAttemptedQuestions()
    {
        return HomeworkQuestions::find()->where(['homework_id' => $this->homework_id])->count();
    }

    public function getExamScores()
    {
        $mode = Utility::getChildMode($this->id);
        $model = QuizSummary::find()
            ->alias('q')
            ->select(['ROUND((SUM(q.correct)/SUM(q.total_questions))*100) score'])
            ->where(['q.student_id' => $this->id, 'submit' => 1, 'mode' => $mode])->asArray()->one();
        return $model['score'];
    }

    public function getLeaderBoard()
    {

        $mode = Utility::getChildMode($this->id);

        // This look promising
//        $connection = Yii::$app->getDb();
//        $command = $connection->createCommand("
//                SELECT id, student_id, SUM(correct) correct, FIND_IN_SET(correct, (
//                SELECT GROUP_CONCAT( correct
//                ORDER BY correct DESC )
//                FROM quiz_summary )
//                ) AS ranks
//                FROM quiz_summary
//                WHERE submit = 1 AND mode = :mode
//                GROUP BY student_id ORDER BY ranks ASC LIMIT 50",[':mode'=>$mode]);
//
//        return $result = $command->queryAll();


        $model = QuizSummary::find()
            //->alias('q')
            ->select([
                new Expression('SUM(correct) score'),
                'quiz_summary.student_id'
            ])
            ->leftJoin('homeworks h','h.id = quiz_summary.homework_id')
            ->with(['student'])
            ->where(['submit' => 1, 'quiz_summary.mode' => $mode,'quiz_summary.subject_id'=>$this->selectedSubject->id, 'h.exam_type_id'=>$this->selectedExam->id])->groupBy('student_id')

            ->orderBy('score DESC')
            ->limit(5)
            ->asArray()
            ->all();
        return $model;
    }
}
