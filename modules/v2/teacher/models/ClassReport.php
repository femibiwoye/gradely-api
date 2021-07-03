<?php

namespace app\modules\v2\teacher\models;

use app\modules\v2\components\Adaptivity;
use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentMastery;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\{Subjects, SubjectTopics, QuizSummaryDetails, VideoContent};
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\TutorSession;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use Yii;
use yii\base\Model;
use app\modules\v2\components\SharedConstant;
use yii\db\Expression;

/**
 * Password reset request form
 */
class ClassReport extends Model
{
    public function getReport()
    {
        return [
            'subjects' => $this->subjects,
            'current_subject' => $this->currentSubject,
            'current_term' => $this->currentTerm,
            'topic_list' => $this->topicList,
            'current_topic' => $this->currentTopic,
            'topic_performance' => $this->topicPerformance,
        ];
    }

    public function getClassPerformanceReport()
    {
        return [
            'subjects' => $this->subjects,
            'current_subject' => $this->currentSubject,
            'current_term' => $this->currentTerm,
            'topic_list' => $this->topicList,
            'studentStat' => $this->studentStat,
            'topicPerformance' => $this->topicClassPerformance
        ];
    }

    public function getClassStudentPerformanceReport()
    {
        return $this->student;
    }

    public function getSubjects()
    {
//        return Subjects::find()
//            ->innerJoin('class_subjects', 'class_subjects.subject_id = subjects.id')
//            ->where(['class_subjects.class_id' => Yii::$app->request->get('class_id')])
//            ->all();
        return Subjects::find()
            ->select([
                'subjects.id',
                'name',
                'slug',
                'description',
                'image',
            ])
            ->innerJoin('teacher_class_subjects tcs', 'tcs.subject_id = subjects.id')
            ->where(['tcs.class_id' => Yii::$app->request->get('class_id')])
            ->all();
    }

    public function getCurrentSubject()
    {
        if (Yii::$app->request->get('subject_id') && !empty(Yii::$app->request->get('subject_id'))) {
            return Subjects::findOne(['id' => Yii::$app->request->get('subject_id')]);
        }

        return $this->subjects ? $this->subjects[0] : null;
    }

    public function getCurrentTerm()
    {
        if (Yii::$app->request->get('term')) {
            return Yii::$app->request->get('term');
        }
        if (Yii::$app->user->identity->type == 'teacher') {
            $teacherClass = TeacherClass::findOne(['teacher_id' => Yii::$app->user->id, 'class_id' => Yii::$app->request->get('class_id')]);
            if (isset($teacherClass->school_id))
                $term = strtolower(SessionTermOnly::widget(['id' => $teacherClass->school_id]));
            else
                $term = strtolower(SessionTermOnly::widget(['nonSchool' => true]));

        } elseif (Yii::$app->user->identity->type == 'school') {
            $id = Schools::findOne(['id' => Utility::getSchoolAccess()])->id;
            $term = strtolower(SessionTermOnly::widget(['id' => $id]));
        }

        return $term;
    }

    public function getTopicList()
    {
        $term = $this->currentTerm;
        $subject = $this->currentSubject;

        try {
            $class = Classes::findOne(['id' => Yii::$app->request->get('class_id')]);

            $record = SubjectTopics::find()
                ->select([
                    'subject_topics.id',
                    'topic',
                    'subject_topics.term',
                    'subject_topics.image'
                ])
                ->innerJoin('questions q', 'q.topic_id = subject_topics.id')
                ->leftJoin('homeworks h', 'h.school_id = ' . $class->school_id)
                ->leftJoin('quiz_summary qs', 'qs.homework_id = h.id')
                ->where(['subject_topics.term' => $term, 'subject_topics.subject_id' => $subject->id, 'subject_topics.class_id' => $class->global_class_id])
                ->groupBy('subject_topics.id')
                ->orderBy(['qs.id' => 'ASC', 'subject_topics.week_number' => 'ASC'])
                ->all();

            return $record;
        } catch (\Exception $exception) {
            return null;
        }
        /*if (Yii::$app->request->get('term')) {
            $record = $record->andWhere(['subject_topics.term' => Yii::$app->request->get('term')]);
        }

        if (Yii::$app->request->get('subject')) {
            $record = $record->andWhere(['subject_topics.subject_id' => $this->currentSubject->id]);
        }

        return $record->all();*/
    }

    public function getCurrentTopic()
    {
        $subject = $this->currentSubject;
        if (Yii::$app->request->get('topic_id')) {
            return SubjectTopics::findOne(['id' => Yii::$app->request->get('topic_id'), 'subject_id' => $subject->id]);
        }


        return isset($this->topicList[0]) ? $this->topicList[0] : null;

    }

    /**
     * This is to get performances of students in excellence, struggling and average
     * @return array
     */
    public function getStudentStat()
    {
        $class = Yii::$app->request->get('class_id');
        if (isset($this->currentSubject->id)) {
            $subject_id = $this->currentSubject->id;
        } else {
            $subject_id = null;
        }
        $term = $this->currentTerm;

        $students = User::find()
            ->select([
                'user.id',
//                'user.firstname',
//                'user.lastname',
//                'user.email',
//                Utility::ImageQuery('user', 'users'),
//                'user.type',
                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                // new Expression('COUNT(qsd.id) as attempt'),
                //new Expression('SUM(case when qsd.selected = qsd.answer then 1 else 0 end) as correct'),
            ])
            ->innerJoin('student_school sc', "sc.student_id = user.id AND sc.class_id = '$class' AND sc.status=1 AND sc.is_active_class = 1 AND sc.current_class = 1")
            ->innerJoin('quiz_summary qs', "qs.student_id = user.id AND qs.subject_id = $subject_id AND qs.submit = 1 AND qs.class_id = $class AND qs.mode = 'practice' AND qs.term = '$term'")
            ->innerJoin('quiz_summary_details qsd', "qsd.quiz_id = qs.id")
            ->where(['AND', ['user.type' => 'student'], ['<>', 'user.status', SharedConstant::STATUS_DELETED]])
            ->groupBy('user.id')
            ->orderBy('score')
            ->asArray()
            ->all();

//        $excellence = [];
//        $average = [];
//        $struggling = [];
        $excellence = 0;
        $average = 0;
        $struggling = 0;

        foreach ($students as $student) {
            if ($student['score'] >= 75) {
                //$excellence[] = $student;
                $excellence++;
            } elseif ($student['score'] >= 40 && $student['score'] < 75) {
                $average++;
            } elseif ($student['score'] >= 0 && $student['score'] < 40) {
                $struggling++;
            }
        }

        $studentsCount = StudentSchool::find()->where(['class_id' => $class, 'status' => SharedConstant::VALUE_ONE, 'is_active_class' => 1, 'current_class' => 1])->count();
        $strugglingExtra = $studentsCount - ($struggling + $excellence + $average);

        return ['studentsCount' => (int)$studentsCount, 'excellence' => $excellence, 'average' => $average, 'struggling' => $struggling + $strugglingExtra];
    }

    /**
     * This is for student mastery in class or single topic
     * @return array
     */
    public function getStudent()
    {
        $class = Yii::$app->request->get('class_id');
        if (isset($this->currentSubject->id)) {
            $subject_id = $this->currentSubject->id;
        } else {
            $subject_id = null;
        }
        $term = $this->currentTerm;

        $students = User::find()
            ->select([
                'user.id',
                new Expression("CONCAT(user.firstname,' ',user.lastname) as fullname"),
                'user.code',
                'user.image',
                Utility::ImageQuery('user', 'users'),
                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as average_score'),
                //new Expression('round((SUM(qs.correct)/SUM(qs.total_questions))*100) as average_score'),
            ])
            ->innerJoin('student_school sc', "sc.student_id = user.id AND sc.class_id = '$class' AND sc.status=1 AND sc.is_active_class = 1 AND sc.current_class = 1")
            ->leftJoin('quiz_summary qs', "qs.student_id = user.id AND qs.subject_id = $subject_id AND qs.submit = 1 AND qs.class_id = $class AND qs.mode = 'practice' AND qs.term = '$term'");
        if ($topicID = Yii::$app->request->get('topic_id')) {
            $students = $students->leftJoin('quiz_summary_details qsd', "qsd.quiz_id = qs.id AND qsd.topic_id = $topicID");
        }else{
            $students = $students->leftJoin('quiz_summary_details qsd', "qsd.quiz_id = qs.id");
        }
        $students = $students->where(['AND', ['user.type' => 'student'], ['<>', 'user.status', SharedConstant::STATUS_DELETED]])
            ->groupBy('user.id')
            ->orderBy('average_score DESC')
            ->asArray()
            ->all();

        $studentFinal = [];
        foreach ($students as $student) {

            $masteryModel = new StudentMastery();
//            $improvementValues = $masteryModel->getImprovementEntry($improvement);

            // shuffle($directions);
            $studentFinal[] = array_merge($student, ['mastery' => array_merge($this->getMastery($student['id']), ['improvement' => 3, 'direction' => 'up'])]);
        }
        return $studentFinal;
    }

    public function getMastery($studentID = null)
    {
        $model = new StudentMastery();
        $model->student_id = !empty($studentID) ? $studentID : $this->id;
        $model->term = Yii::$app->request->get('term');
        $model->subject = isset($this->currentSubject['id']) ? $this->currentSubject['id'] : null;
        $model->class = Classes::findOne(['id' => Yii::$app->request->get('class_id')])->global_class_id;

        if (!$model->validate()) {
            return ['total' => 0, 'score' => 0, 'percentage' => 0];
        }
        return $model->getPerformanceSummary();
    }

    public function getTopicClassPerformance()
    {

        $class = Yii::$app->request->get('class_id');
        if (isset($this->currentSubject->id)) {
            $subject_id = $this->currentSubject->id;
        } else {
            $subject_id = null;
        }
        $term = $this->currentTerm;

        $excellence = [];

        $excellenceQuery = (new \yii\db\Query())
            ->from('quiz_summary_details qsd')
            ->select([
                'qsd.topic_id',
                'st.topic',
                new Expression('SUM(case when qsd.selected = qsd.answer then 1 else 0 end) as correct'),
                new Expression("'excellence' as level"),

            ])
            ->innerJoin('quiz_summary qs', "qs.id = qsd.quiz_id AND qs.subject_id = $subject_id AND qs.submit = 1 AND qs.class_id = $class AND qs.mode = 'practice' AND qs.term = '$term'")
            ->leftJoin('subject_topics st', 'st.id = qsd.topic_id')
            ->having(['>=', 'correct', 50])
            ->groupBy(['topic_id'])
            ->orderBy('correct DESC')
            ->limit(50);

        $strugglingQuery = (new \yii\db\Query())
            ->from('quiz_summary_details qsd')
            ->select([
                'qsd.topic_id',
                'st.topic',
                new Expression('SUM(case when qsd.selected = qsd.answer then 1 else 0 end) as correct'),
                new Expression("'struggling' as level"),

            ])
            ->innerJoin('quiz_summary qs', "qs.id = qsd.quiz_id AND qs.subject_id = $subject_id AND qs.submit = 1 AND qs.class_id = $class AND qs.mode = 'practice' AND qs.term = '$term'")
            ->leftJoin('subject_topics st', 'st.id = qsd.topic_id')
            ->having(['<', 'correct', 50])
            ->groupBy(['topic_id'])
            ->orderBy('correct')
            ->limit(50);

        $excellenceQuery->union($strugglingQuery);


        return $query = QuizSummaryDetails::find()->select('*')->from(['random_name' => $excellenceQuery])->groupBy('topic_id')->asArray()->all();

    }


    public function getTopicPerformance()
    {
        $class = Yii::$app->request->get('class_id');
        if (isset($this->currentTopic->id)) {
            $topic_id = $this->currentTopic->id;
        } else {
            $topic_id = null;
        }

        $students = User::find()
            ->select([
                'user.id',
                'user.firstname',
                'user.lastname',
                'user.email',
                Utility::ImageQuery('user', 'users'),
                'user.type',
                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                new Expression('COUNT(qsd.id) as attempt'),
                new Expression('SUM(case when qsd.selected = qsd.answer then 1 else 0 end) as correct')
            ])
            ->innerJoin('student_school sc', "sc.student_id = user.id AND sc.class_id = '$class' AND sc.status=1")
            ->innerJoin('quiz_summary_details qsd', "qsd.student_id = user.id AND qsd.topic_id = '$topic_id'")
            ->innerJoin('quiz_summary qs', "qs.id = qsd.quiz_id AND qs.submit = 1")
            ->where(['AND', ['user.type' => 'student'], ['<>', 'user.status', SharedConstant::STATUS_DELETED]])
            ->groupBy('user.id')
            ->orderBy('score')
            ->asArray()
            ->all();

        $excellence = [];
        //$average = [];
        $struggling = [];

        foreach ($students as $student) {

            array_push($student, $this->getRecommendations($student['id']));
            $student['recommendations'] = $student['score'] < 75 ? $student[SharedConstant::VALUE_ZERO] : [];
            unset($student[SharedConstant::VALUE_ZERO]);

            if ($student['score'] >= 75) {
                $excellence[] = $student;
            } elseif ($student['score'] >= 40 && $student['score'] < 75) {
                $average[] = $student;
            } elseif ($student['score'] >= 0 && $student['score'] < 40) {
                $struggling[] = $student;
            }
        }

        $studentsCount = StudentSchool::find()->where(['class_id' => $class, 'status' => SharedConstant::VALUE_ONE])->count();

        return ['studentsCount' => $studentsCount, 'excellence' => $excellence, 'average' => $average, 'struggling' => $struggling];

    }


    public function getTopicPerformanceBk()
    {
        $record = SubjectTopics::find()
            ->innerJoin('classes', 'classes.global_class_id = subject_topics.class_id')
            ->where(['classes.id' => Yii::$app->request->get('class_id')])
            ->one();

        return $record->score;

    }

    private function getRecommendations($student_id)
    {
        return [
            'remedial' => $this->getLowestTopicAttempted($student_id),
            'resources' => $this->getResources($this->getLowestTopicAttempted($student_id)['topic_id'], $student_id),
        ];

    }

    private function getLowestTopicAttempted($student_id)
    {

        $topics_attempted = array();
        $topics = QuizSummaryDetails::find()
            ->alias('s')
            ->select(['s.topic_id'])
            ->where(['s.student_id' => $student_id])
            ->innerJoin('quiz_summary q', 'q.id = s.quiz_id AND q.submit = 1')
            ->groupBy('topic_id');

        if ($this->currentSubject)
            $topics = $topics->innerJoin('subjects sb', 'sb.id = ' . $this->currentSubject->id);

        $topics = $topics->asArray()
            ->all();

        foreach ($topics as $topic) {
            $attempted_topic = QuizSummaryDetails::find()
                ->alias('qsd')
                ->leftJoin('subject_topics st', 'st.id = qsd.topic_id')
                ->select([
                    new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                    //new Expression('(case when (select requester_id from tutor_session where student_id = qsd.student_id AND meta = "recommendation" AND requester_id = '.Yii::$app->user->id.') then 1 else 0 end) as is_recommended'),
                    'qsd.topic_id',
                    'st.topic',
                    'st.subject_id'
                ])
                ->where([
                    'topic_id' => $topic, 'student_id' => $student_id])
                ->asArray()
                ->all();

            $topics_attempted = array_merge($topics_attempted, $attempted_topic);
        }

        return $this->getLowestAttemptedTopic($topics_attempted, $student_id);
    }

    private function getLowestAttemptedTopic($attempted_topics, $studentID)
    {
        $least_attempted_topic = array();
        foreach ($attempted_topics as $attempted_topic) {
            if (empty($least_attempted_topic)) {
                $least_attempted_topic = $attempted_topic;
            }

            if ($least_attempted_topic['score'] >= $attempted_topic['score']) {
                $least_attempted_topic = $attempted_topic;
            }
        }

        $recommended = TutorSession::find()->where(['student_id' => $studentID, 'meta' => 'recommendation', 'requester_id' => Yii::$app->user->id, 'status' => 'pending'])->exists() ? 1 : 0;

        return array_merge($least_attempted_topic, ['is_recommended' => $recommended]);
    }

    private function getResources($topic_id, $student_id)
    {
        $classID = Yii::$app->request->get('class_id');
        $practiceVideosRecommendations = Adaptivity::PracticeVideoRecommendation($topic_id, $student_id, 'class', $classID, $this->currentSubject->id);

        if (!$practiceVideosRecommendations) {
            return SharedConstant::VALUE_NULL;
        }

        return $practiceVideosRecommendations;
    }
}
