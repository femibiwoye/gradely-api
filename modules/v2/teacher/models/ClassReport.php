<?php

namespace app\modules\v2\teacher\models;

use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Schools;
use app\modules\v2\models\{Subjects, SubjectTopics};
use app\modules\v2\models\TeacherClass;
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
        if (Yii::$app->request->get('subject')) {
            return Subjects::findOne(['slug' => Yii::$app->request->get('subject')]);
        }
        return $this->subjects[0];
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

        $class = Classes::findOne(['id' => Yii::$app->request->get('class_id')]);

        $record = SubjectTopics::find()->where(['term' => $term, 'subject_id' => $subject->id, 'class_id' => $class->global_class_id])->all();

        return $record;
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

        return $this->topicList[0];

    }

    public function getTopicPerformance()
    {
        $class = Yii::$app->request->get('class_id');
        $topic_id = $this->currentTopic->id;

        return $students = UserModel::find()
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
            ->asArray()
            ->all();

        $excellence = [];
        $average = [];
        $struggling = [];
        foreach ($students as $student) {
            if ($student['score'] >= 75) {
                $excellence[] = $student;
            } elseif ($student['score'] >= 50 && $student['score'] < 75) {
                $average[] = $student;
            } elseif ($student['score'] < 50) {
                $struggling[] = $student;
            }
        }

        return ['studentsCount' => count($students), 'excellence' => $excellence, 'average' => $average, 'struggling' => $struggling];

    }

    public function getTopicPerformanceBk()
    {
        if (Yii::$app->request->get('topic_id')) {

        }

        $record = SubjectTopics::find()
            ->innerJoin('classes', 'classes.global_class_id = subject_topics.class_id')
            ->where(['classes.id' => Yii::$app->request->get('class_id')])
            ->one();

        return $record->score;

    }
}
