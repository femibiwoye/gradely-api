<?php

namespace app\modules\v2\models;

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
            'image',
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
            'completion_rate' => 'totalHomeworks',
            'positions' => 'statistics',
            'performance' => 'performance',
            'feeds' => 'feeds'
        ];
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
            ->where(['q.student_id' => $this->id])
            ->joinWith(['childHomework', 'subject']);

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

        $class = StudentSchool::findOne(['student_id' => $this->id, 'status' => 1]);

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
            ->where(['AND', $condition, [/*'class_id' => $class->class_id,*/ 'status' => 1]]);
        if (Yii::$app->request->get('subject'))
            $homeworkCount = $homeworkCount->andWhere(['homeworks.subject_id' => Yii::$app->request->get('subject')]);
        $homeworkCount = $homeworkCount->count();

        $studentCount = QuizSummary::find()
            ->alias('q')
            ->where(['q.student_id' => $this->id/*, 'q.class_id' => $class->class_id*/, 'submit' => 1])
            ->innerJoin('homeworks', "homeworks.id = q.homework_id" . $condition2);

        if (Yii::$app->request->get('subject'))
            $studentCount = $studentCount->andWhere(['q.subject_id' => Yii::$app->request->get('subject')]);
        if (Yii::$app->request->get('term'))
            $studentCount = $studentCount->andWhere(['q.term' => Yii::$app->request->get('term')]);

        $studentCount = $studentCount->count();

        return $homeworkCount > 0 ? round($studentCount / $homeworkCount * 100) : 0;

        $attempted_questions = 0;
        foreach ($this->homework as $homework) {
            if ($homework->quizSummary && $homework->quizSummary->submit == SharedConstant::VALUE_ONE) {
                $attempted_questions = $attempted_questions + 1;
            }
        }

        if ($attempted_questions > 0)
            $attempted_questions = ($attempted_questions / count($this->homework)) * 100;


        return $attempted_questions;
    }

    public function getTopicBreakdown()
    {
        
        $topics = SubjectTopics::find();

        if (Yii::$app->request->get('subject'))
            $topics = $topics->andWhere(['subject_topics.subject_id' => Yii::$app->request->get('subject')]);
        if (Yii::$app->request->get('term'))
            $topics = $topics->andWhere(['subject_topics.term' => Yii::$app->request->get('term')]);

        $topics = $topics->innerJoin('quiz_summary q', 'q.id = subject_topics.id AND q.submit = 1')
            ->groupBy('topic_id')
            ->asArray()
            ->all();

        $groupPerformance = [];
        foreach ($topics as $topic) {
            $summary = QuizSummaryDetails::find()
                ->where(['topic_id' => $topic, 'student_id' => $this->id]);

            $totalAttempt = $summary->count();
            $correctAttempt = $summary->andWhere(['=', 'selected', new Expression('`answer`')])->count();
            $this->getQuestionsDifficulty($summary, $topic);
            $topicScore = ($correctAttempt / ($totalAttempt == 0 ? 1 : $totalAttempt)) * 100;

            $statistics = ['score' => $topicScore, 'attempted' => $totalAttempt, 'correct' => $correctAttempt, 'improvement' => null, 'direction' => null]; //Direction is up|down
            $topic_progress = ['hard' => $this->hard_questions, 'medium' => $this->medium_questions, 'easy' => $this->easy_questions, 'score' => $this->score, 'improvement' => $this->improvement, 'direction' => $this->direction];
            $topicDetails = SubjectTopics::findOne(['id' => $topic]);

            $groupPerformance[] = array_merge(ArrayHelper::toArray($topicDetails), ['stastistics' => $statistics, 'topic_progress' => $topic_progress]);
        }
        return $groupPerformance;
    }

    public function getQuestion($question_id)
    {
        $model = Questions::findOne(['id' => $question_id]);
        return $model->difficulty;
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
            ->where(['student_id' => $this->id, 'topic_id' => $topic])
            //->groupBy('quiz_id')
            ->orderBy(['topic_id' => SORT_DESC])
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

    public function getStatistics()
    {
        $studentAnalytics = new StudentAnalytics();
        return $result = $studentAnalytics->Analytics($this);
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
        $excellence = [];
        $averages = [];
        $struggling = [];

        $activeTopics = QuizSummaryDetails::find()->select(['qsd.topic_id'])
            ->alias('qsd')
            ->where(['qsd.student_id' => $this->id]);


        if (isset($_GET['term'])) {
            $term = $_GET['term'];
            $activeTopics = $activeTopics->innerJoin('quiz_summary qs', "qs.id = qsd.quiz_id AND qs.term ='$term'");
        }
        $activeTopics = ArrayHelper::getColumn($activeTopics->groupBy('qsd.topic_id')->all(), 'topic_id');

        if (!empty($activeTopics)) {

            if (isset($_GET['subject'])) {
                $selectedSubject = Subjects::find()->where(['status' => 1]);
                $selectedSubject = $selectedSubject->andWhere(['slug' => $_GET['subject']]);
                $selectedSubject = ArrayHelper::getColumn($selectedSubject->all(), 'id');
            } else {
                $attemptedSubjects = QuizSummary::find()->where(['student_id' => $this->id])->groupBy('subject_id')->all();
                $selectedSubject = ArrayHelper::getColumn($attemptedSubjects, 'subject_id');
            }

            $topics = SubjectTopics::find()
                ->where([
                    'subject_id' => $selectedSubject,
                    'id' => $activeTopics
                ])
                ->all();

            if (!empty($topics)) {
                foreach ($topics as $data) {
                    if ($data->getTopicPerformanceByID($data->id, $this->id) >= 75) {
                        $excellence[] = $this->topicPerformanceMini($data);
                    } elseif ($data->getTopicPerformanceByID($data->id, $this->id) >= 50 && $data->getTopicPerformanceByID($data->id, $this->id) < 75) {
                        $averages[] = $this->topicPerformanceMini($data);
                    } elseif ($data->getTopicPerformanceByID($data->id, $this->id) < 50) {
                        $struggling[] = $this->topicPerformanceMini($data);
                    }
                }
            }
        }

        $excellence = array_slice($excellence, 0, 5);
        $averages = array_slice($averages, 0, 5);
        $struggling = array_slice($struggling, 0, 5);

        return [
            'excellence' => $excellence,
            'average' => $averages,
            'struggling' => $struggling
        ];

    }

    public function getClassSubjects()
    {
        $studentID = $this->id;
        return Subjects::find()
            ->alias('s')
            ->select([
                's.id',
                's.slug',
                's.name',
                //'s.description',
                //'s.image',
            ])
            ->innerJoin('student_school ss', "ss.student_id = $studentID")
            ->innerJoin('class_subjects cs', 'cs.class_id = ss.class_id AND cs.school_id = ss.school_id AND cs.subject_id = s.id')
            ->where(['s.status' => 1, 'cs.status' => 1])
            ->all();
    }

    private function topicPerformanceMini($data)
    {
        return ['title' => $data->topic, 'id' => $data->id];
    }
}
