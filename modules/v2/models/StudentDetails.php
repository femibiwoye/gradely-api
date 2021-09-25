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
class StudentDetails extends User
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
            'profile' => 'userProfile',
            'parents' => 'studentParents',
            'remarks' => 'remarks',
            'topics' => 'topicBreakdown',
            'homework',
            'subjects' => 'classSubjects',
            'selectedSubject' => 'selectedSubject',
            'completion_rate' => 'totalHomeworks',
            'positions' => 'statistics',
            'performance' => 'performance',
            'feeds' => 'feeds',
            'mastery'
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

    public function getHomework()
    {
        $model = QuizSummary::find()
            ->alias('q')
            ->select(['q.*', '(q.correct/q.total_questions)*100 score'])
            ->where(['q.student_id' => $this->id, 'q.session' => Yii::$app->params['activeSession']])
            ->joinWith(['childHomework', 'subject']);

        if (Yii::$app->user->identity->type == 'school' || Yii::$app->user->identity->type == 'teacher') {
            $model = $model->andWhere(['q.type' => 'homework']);
        } else {
            $model = $model->andWhere(['q.mode' => Utility::getChildMode($this->id)]);
        }

        if (Yii::$app->request->get('subject'))
            $model = $model->andWhere(['q.subject_id' => Yii::$app->request->get('subject')]);
        if (Yii::$app->request->get('term'))
            $model = $model->andWhere(['q.term' => Yii::$app->request->get('term')]);

        return $model->asArray()
            ->all();
    }

    public function getRemarks()
    {
        return Remarks::find()
            ->where(['receiver_id' => $this->id, 'type' => 'student'])
            //->asArray()
            ->all();
    }

    public function getFeeds()
    {
        $model = Feed::find()
            ->alias('f')
            ->select([
                'f.id',
                'f.description',
                'f.token as feed_token',
                'f.created_at',
                'count(fl.id) as likeCount',
                'count(fc.id) as commentCount',
                'pm.title',
                'pm.filename',
                'pm.filetype',
                'pm.filesize',
                'pm.tag',
                'pm.token as attachment_token',
            ])
            ->leftJoin('feed_like fl', "fl.parent_id = f.id AND fl.type = 'feed'")
            ->leftJoin('feed_comment fc', "fc.feed_id = f.id AND fc.type = 'feed'")
            ->leftJoin('practice_material pm', "pm.practice_id = f.id AND pm.type = 'feed'")
            ->where(['f.user_id' => $this->id]);


        if (Yii::$app->request->get('subject'))
            $model = $model->andWhere(['f.subject_id' => Yii::$app->request->get('subject')]);

        return $model->orderBy('id DESC')
            ->limit(5)
            ->groupBy('f.id')
            ->asArray()->all();
    }

    public function getStudentParents()
    {
        return Parents::find()
            ->alias('p')
            ->select([
                'u.id as id',
                'p.role',
                'CONCAT(u.firstname," ",u.lastname) as name',
                "u.image",
                "u.email",
                "u.phone"
            ])
            ->leftJoin('user u', "u.id = p.parent_id AND u.type = 'parent'")
            ->where(['student_id' => $this->id, 'p.status' => 1])
            ->asArray()
            ->all();

    }

    public function getTotalHomeworks()
    {

        $userType = Yii::$app->user->identity->type;
        //$class = StudentSchool::findOne(['student_id' => $this->id, 'status' => 1, 'is_active_class' => 1]);

        if (Yii::$app->user->identity->type == 'teacher') {
            $condition = ['teacher_id' => Yii::$app->user->id];
            $condition2 = " AND homeworks.teacher_id = " . Yii::$app->user->id;
        } elseif (Yii::$app->user->identity->type == 'school') {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            $condition = ['school_id' => Utility::getSchoolAccess()];
            $condition2 = " AND homeworks.school_id = " . $school->id;
        } elseif (Yii::$app->user->identity->type == 'student' || Yii::$app->user->identity->type == 'parent') {
            $condition = ['student_id' => $this->id];
            $condition2 = " AND homeworks.student_id = " . $this->id;
        }

        $homeworkCount = Homeworks::find()
            ->where(['AND', $condition, [/*'class_id' => $class->class_id,*/ 'status' => 1, 'type' => 'homework']]);
        if (Yii::$app->request->get('subject'))
            $homeworkCount = $homeworkCount->andWhere(['homeworks.subject_id' => Yii::$app->request->get('subject')]);

        if (in_array($userType, SharedConstant::EXAM_MODE_USER_TYPE)) {
            $homeworkCount = $homeworkCount->andWhere(['homeworks.mode' => Utility::getChildMode($this->id)]);
        }

        $homeworkCount = $homeworkCount->count();

        $studentCount = QuizSummary::find()
            ->alias('q')
            ->where(['q.student_id' => $this->id/*, 'q.class_id' => $class->class_id*/, 'submit' => 1,'q.session'=>Yii::$app->params['activeSession']])
            ->innerJoin('homeworks', "homeworks.id = q.homework_id AND homeworks.type = 'homework'" . $condition2);

        if (Yii::$app->request->get('subject'))
            $studentCount = $studentCount->andWhere(['q.subject_id' => Yii::$app->request->get('subject')]);
        if (Yii::$app->request->get('term'))
            $studentCount = $studentCount->andWhere(['q.term' => Yii::$app->request->get('term')]);

        if (in_array($userType, SharedConstant::EXAM_MODE_USER_TYPE)) {
            $studentCount = $studentCount->andWhere(['homeworks.mode' => Utility::getChildMode($this->id)]);
        }

        $studentCount = $studentCount->count();

        return $homeworkCount > 0 ? round($studentCount / $homeworkCount * 100) : 0;

//        $attempted_questions = 0;
//        foreach ($this->homework as $homework) {
//            if ($homework->quizSummary && $homework->quizSummary->submit == SharedConstant::VALUE_ONE) {
//                $attempted_questions = $attempted_questions + 1;
//            }
//        }
//
//        if ($attempted_questions > 0)
//            $attempted_questions = ($attempted_questions / count($this->homework)) * 100;
//
//
//        return $attempted_questions;
    }

    public function getTopicBreakdown()
    {
        $userType = Yii::$app->user->identity->type;
        return $this->getTopicBreakdownModel($this->id, $userType);
    }

    public function getTopicBreakdownModel($studentID, $userType, $examID = null)
    {
        $mode = Yii::$app->request->get('mode');
        $subjectID = isset($this->getSelectedSubject()['id']) ? $this->getSelectedSubject()['id'] : null;
        if ($mode != 'exam') {
            $classID = Utility::getStudentClass(1, $studentID);

            $topics = SubjectTopics::find()
                ->andWhere(['class_id' => $classID]);

            if (Yii::$app->request->get('subject'))
                $topics = $topics->andWhere(['subject_id' => Yii::$app->request->get('subject')]);
            else
                $topics = $topics->andWhere(['subject_id' => $subjectID]);

            $topics = $topics->andWhere([
                'term' => Yii::$app->request->get('term') ? strtolower(Yii::$app->request->get('term')) : strtolower(Utility::getStudentTermWeek('term', $studentID))]);

            $topics = $topics
                ->groupBy('id')
                ->asArray()
                ->all();
        } else {
            $topics = SubjectTopics::find()
                ->select(['subject_topics.id AS id'])
                ->leftJoin('questions', "questions.topic_id = subject_topics.id")
                ->where([
                    'subject_topics.subject_id' => $subjectID,
                    'questions.category' => $mode,
//                    'subject_topics.id'=>[14,300,159], //to be removed
                    'questions.exam_type_id' => $examID
                ])->asArray()->all();
        }

        $groupPerformance = [];
        foreach ($topics as $index => $topic) {
            $this->hard_questions = 0;
            $this->medium_questions = 0;
            $this->easy_questions = 0;
            $this->score = 0;
            $this->improvement = null;
            $this->direction = null;

            $topic = $topic['id'];
            $summary = QuizSummaryDetails::find()
                ->alias('qsd')
                ->select([
                    'q.difficulty',
                    'qsd.question_id AS question_id',
                    'qsd.id as id',
                    'qsd.selected',
                    'qsd.answer',
                    'qsd.topic_id',
                    'qsd.created_at',
                    'qsd.homework_id',
                ])
                ->leftJoin('questions q', 'qsd.question_id = q.id')
                ->where(['qsd.topic_id' => $topic, 'qsd.student_id' => $studentID]);


            if (in_array($userType, SharedConstant::EXAM_MODE_USER_TYPE)) {
                $summary = $summary
                    ->leftJoin('homeworks h', 'h.id = qsd.homework_id')
                    ->andWhere(['h.mode' => Utility::getChildMode($studentID)]);

                if ($mode == 'exam') {
                    $summary = $summary->andWhere(['h.exam_type_id' => $examID]);
                }
            }


            //$totalAttempt = $summary->count();
            //$correctAttempt = $summary->andWhere(['=', 'selected', new Expression('`answer`')])->count();

            //$topicScore = ($correctAttempt / ($totalAttempt == 0 ? 1 : $totalAttempt)) * 100;
            $topic_progress = $this->getTopicMastery($summary, $topic, $studentID);
            //$statistics = ['score' => round($topicScore), 'attempted' => $totalAttempt, 'correct' => $correctAttempt, 'improvement' => null, 'direction' => null]; //Direction is up|down
            //$topic_progress = ['hard' => $this->hard_questions, 'medium' => $this->medium_questions, 'easy' => $this->easy_questions, 'score' => round($this->score), 'improvement' => $this->improvement, 'direction' => $this->direction];
            $topicDetails = SubjectTopics::findOne(['id' => $topic]);

            $groupPerformance[] = array_merge(ArrayHelper::toArray($topicDetails), [
                //'statistics' => $statistics,
                'topic_progress' => $topic_progress
            ]);
            if ($index >= 9) {
                break;
            }
        }
        return $groupPerformance;
    }

    public function getQuestion($question_id)
    {
        $model = Questions::findOne(['id' => $question_id]);
        return isset($model->difficulty) ? $model->difficulty : SharedConstant::QUESTION_DIFFICULTY[2];
    }

    public function getTopicMastery($summary, $topic, $studentID)
    {
        $summaryClone = clone $summary;
        $improvement = $this->getImprovementRate($summaryClone, $topic, $studentID);
//        $correctAttempts = $summary->andWhere(['=', 'selected', new Expression('`qsd`.`answer`')])->asArray()->all(); //To be removed
        $correctAttempts = $summary->andWhere(['is_correct' => 1])->asArray()->all();
        if (count($correctAttempts) < 1) {
            return ['hard' => 0, 'medium' => 0, 'easy' => 0, 'score' => 0, 'direction' => null, 'improvement' => null];
        }

        $masteryModel = new StudentMastery();
        return array_merge($masteryModel->TopicImprovement($correctAttempts, $topic), $improvement);
    }

    public function getImprovementRate($summary, $topic, $studentID)
    {


        $masteryModel = new StudentMastery();

        $models = $summary
            ->where(['qsd.student_id' => $studentID, 'qsd.topic_id' => $topic])
            ->andWhere(['>', 'qsd.created_at', new Expression('DATE_SUB(NOW(), INTERVAL 2 WEEK)')])
            ->orderBy(['qsd.topic_id' => SORT_DESC])
            ->asArray()
            ->all();

        return $masteryModel->getImprovementEntry($models);

    }


    public function getQuestionsDifficultyBK($summary, $topic, $studentID)
    {
        $this->getRecentAttempts($summary, $topic, $studentID);
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

    public function getImprovements($studentID, $topic = null)
    {
        $easy_questions = SharedConstant::VALUE_ZERO;
        $medium_questions = SharedConstant::VALUE_ZERO;
        $hard_questions = SharedConstant::VALUE_ZERO;
        $score = [];
        $model = Homeworks::find()
            ->alias('h')
            ->select([
                new Expression('YEAR(h.created_at)'),
                new Expression('MONTH(h.created_at)'),
                new Expression('WEEK(h.created_at)'),
            ])
            ->where(['h.student_id' => $studentID, 'h.mode' => Utility::getChildMode($studentID)]);
    }

    public function getRecentAttempts($summary, $topic, $studentID)
    {
        $easy_questions = SharedConstant::VALUE_ZERO;
        $medium_questions = SharedConstant::VALUE_ZERO;
        $hard_questions = SharedConstant::VALUE_ZERO;
        $score = [];
        $recent_attempts = $summary
            ->where(['quiz_summary_details.student_id' => $studentID, 'quiz_summary_details.topic_id' => $topic])
            //->groupBy('quiz_id')
            ->orderBy(['quiz_summary_details.topic_id' => SORT_DESC])
            ->limit(2);

        //SELECT MONTH(date) AS MONTH, WEEK(date) AS WEEK, DATE_FORMAT(date, %Y-%m-%d) AS DATE, job AS JOB, person AS PERSON GROUP BY WEEK(date);

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

    public function getStatistics()
    {
        if (isset($_GET['term']))
            $term = $_GET['term'];
        else
            $term = Utility::getStudentTermWeek('term', $this->id);


        $studentAnalytics = new StudentAnalytics();
        $subject_id = isset($this->getSelectedSubject()->id) ? $this->getSelectedSubject()->id : null;

        return $result = $studentAnalytics->Analytics($this, $subject_id, $term);
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
        return $this->getPerformanceModel($this->id);
    }

    public function getPerformanceModel($studentID)
    {
        $excellence = [];
        $averages = [];
        $struggling = [];
        $mode = Utility::getChildMode($studentID);
        $session = Yii::$app->params['activeSession'];
        $activeTopics = QuizSummaryDetails::find()->select(['qsd.topic_id'])
            ->alias('qsd')
            ->leftJoin('quiz_summary qz', 'qz.id = qsd.quiz_id')
            ->where(['qsd.student_id' => $studentID, 'qz.session' => $session]);

        if (in_array(Yii::$app->user->identity->type, SharedConstant::EXAM_MODE_USER_TYPE)) {
            $activeTopics = $activeTopics
                ->andWhere(['qz.mode' => $mode]);
        }

        if (isset($_GET['term'])) {
            $term = $_GET['term'];
            $activeTopics = $activeTopics->innerJoin('quiz_summary qs', "qs.id = qsd.quiz_id AND qs.term ='$term' AND qs.session = '$session'");
        }

        $activeTopics = ArrayHelper::getColumn($activeTopics->groupBy('qsd.topic_id')->all(), 'topic_id');

        if (!empty($activeTopics)) {
            if (isset($_GET['subject']) && !empty($_GET['subject'])) {
                $selectedSubject = Subjects::find()->where(['status' => 1]);
                $selectedSubject = $selectedSubject->andWhere(['id' => $_GET['subject']]);
                $selectedSubject = ArrayHelper::getColumn($selectedSubject->all(), 'id');
            } else {
//                $attemptedSubjects = QuizSummary::find()->where(['student_id' => $this->id])->groupBy('subject_id')->all();
//                $selectedSubject = ArrayHelper::getColumn($attemptedSubjects, 'subject_id');
                $selectedSubject = isset($this->getSelectedSubject()['id']) ? $this->getSelectedSubject()['id'] : null;
            }

            $topics = SubjectTopics::find()
                ->where([
                    'subject_id' => $selectedSubject,
                    'id' => $activeTopics
                ])
                ->all();

            if (!empty($topics)) {
                foreach ($topics as $data) {
                    if ($data->getResultClass($studentID, $data->id, $mode) >= 75) {
                        $excellence[] = $this->topicPerformanceMini($data);
                    } elseif ($data->getResultClass($studentID, $data->id, $mode) >= 50 && $data->getResult($studentID, $data->id) < 75) {
                        $averages[] = $this->topicPerformanceMini($data);
                    } elseif ($data->getResultClass($studentID, $data->id, $mode) < 50) {
                        $struggling[] = $this->topicPerformanceMini($data);
                    }
                }
            }
        }

        $excellence = array_slice($excellence, 0, 3);
        $averages = array_slice($averages, 0, 3);
        $struggling = array_slice($struggling, 0, 3);

        return [
            'excellence' => $excellence,
            'average' => $averages,
            'struggling' => $struggling
        ];

    }

    public function getClassSubjects($subject_id = null)
    {
        $studentID = Utility::getParentChildID();

        $subjectIDS = ArrayHelper::getColumn(QuizSummary::find()->select(['subject_id'])->where(['student_id' => $studentID, 'submit' => 1, 'session' => Yii::$app->params['activeSession']])->groupBy('subject_id')->all(), 'subject_id');
        $subjects = Subjects::find()
            ->alias('s')
            ->select([
                's.id',
                's.slug',
                's.name',
                //'s.description',
                //'s.image',
            ])
            ->leftJoin('student_school ss', "ss.student_id = '$studentID' AND ss.status = 1")
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

    private function topicPerformanceMini($data)
    {
        return ['title' => $data->topic, 'id' => $data->id];
    }

    public function getMastery()
    {
        $model = new StudentMastery();
        $model->student_id = $this->id;
        $model->term = Yii::$app->request->get('term');
        $model->subject = isset($this->getSelectedSubject()['id']) ? $this->getSelectedSubject()['id'] : null;
        $model->mode = Utility::getChildMode($this->id);;
        if (!$model->validate()) {
            return ['total' => 0, 'score' => 0, 'percentage' => 0];
        }
        return $model->getPerformanceSummary();
    }
}
