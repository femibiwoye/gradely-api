<?php

namespace app\modules\v2\teacher\models;

use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\{Subjects, SubjectTopics, QuizSummaryDetails, VideoContent};
use app\modules\v2\models\TeacherClass;
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

    public function getSubjects()
    {
        return Subjects::find()
            ->innerJoin('class_subjects', 'class_subjects.subject_id = subjects.id')
            ->where(['class_subjects.class_id' => Yii::$app->request->get('class_id')])
            ->all();
    }

    public function getCurrentSubject()
    {
        if (Yii::$app->request->get('subject') && !empty(Yii::$app->request->get('subject'))) {
            return Subjects::findOne(['slug' => Yii::$app->request->get('subject')]);
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

            $record = SubjectTopics::find()->where(['term' => $term, 'subject_id' => $subject->id, 'class_id' => $class->global_class_id])->all();

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
                'user.image',
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
        $average = [];
        $struggling = [];

        foreach ($students as $student) {
            array_push($student, $this->getRecommendations($student['id']));
            $student['recommendations'] = $student[SharedConstant::VALUE_ZERO];
            unset($student[SharedConstant::VALUE_ZERO]);
            if ($student['score'] >= 75) {
                $excellence[] = $student;
            } elseif ($student['score'] >= 50 && $student['score'] < 75) {
                $average[] = $student;
            } elseif ($student['score'] >= 0 && $student['score'] < 50) {
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
            'resources' => $this->getResources($this->getLowestTopicAttempted($student_id)['topic_id']),
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
                    'qsd.topic_id',
                    'st.topic',
                    'st.subject_id'
                ])
                ->where(['topic_id' => $topic, 'student_id' => $student_id])
                ->asArray()
                ->all();

            $topics_attempted = array_merge($topics_attempted, $attempted_topic);
        }

        return $this->getLowestAttemptedTopic($topics_attempted);
    }

    private function getLowestAttemptedTopic($attempted_topics)
    {
        print_r($attempted_topics);
        die;
        $least_attempted_topic = array();
        foreach ($attempted_topics as $attempted_topic) {
            if (empty($least_attempted_topic)) {
                $least_attempted_topic = $attempted_topic;
            }

            if ($least_attempted_topic['score'] >= $attempted_topic['score']) {
                $least_attempted_topic = $attempted_topic;
            }
        }

        return $least_attempted_topic;
    }

    private function getResources($topic_id)
    {
        $topic_objects = SubjectTopics::find()
            ->select([
                'subject_topics.*',
                new Expression("'practice' as type")
            ])
            ->where(['id' => $topic_id])
            ->asArray()
            ->one();

        //retrieves assign videos to the topic
        $video = VideoContent::find()
            ->select([
                'video_content.*',
                new Expression("'video' as type")
            ])
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->where(['video_assign.topic_id' => 'topic_id'])
            ->limit(SharedConstant::VALUE_THREE)
            ->asArray()
            ->all();

        if (!$topic_objects) {
            return SharedConstant::VALUE_NULL;
        }


        return array_merge($topic_objects, $video);
    }
}
