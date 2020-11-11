<?php

namespace app\modules\v2\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use app\modules\v2\components\Utility;

class StudentMastery extends Model
{

    public $student_id;
    public $subject_id;
    public $class_id;


    public function rules()
    {
        return [
            [['student_id'], 'required', 'when' => function ($model) {
                return Yii::$app->user->identity->type == 'parent';
            }],
            [['student_id', 'class_id', 'subject_id'], 'integer'],
            [['student_id'], 'exist', 'targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']],
            [['subject_id'], 'exist', 'targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']],
            [['class_id'], 'exist', 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
            [['class_id'], 'exist', 'targetClass' => StudentSchool::className(), 'targetAttribute' => ['class_id' => 'class_id', 'student_id' => 'student_id']],
            [['student_id'], 'exist', 'targetClass' => Parents::className(), 'targetAttribute' => ['student_id' => 'student_id', 'parent_id' => Yii::$app->user->id], 'when' => function ($model) {
                return Yii::$app->user->identity->type == 'parent';
            }],
        ];
    }

    public function getData()
    {
        return [
            'id' => $this->getUser()->id,
            'name' => $this->getName(),
            'image' => $this->getUser()->image,
            'subjects' => $this->getSubjects(),
            'current_subject' => $this->getCurrentSubject(),
            'classes' => $this->getClasses(),
            'current_class' => $this->getCurrentClass(),
            'report' => $this->getReport()
        ];
    }

    public function getGlobalData()
    {
        return [
            'id' => $this->getUser()->id,
            'name' => $this->getName(),
            'image' => $this->getUser()->image,
            'subjects' => $this->getClassSubjects(),
            'current_subject' => $this->getCurrentClassSubject(),
            'terms' => $this->getClassTerms(),
            'classes' => $this->getGlobalClasses(),
            'current_class' => $this->getCurrentClass(),
        ];
    }

    private function getName()
    {
        return $this->getUser()->firstname . ' ' . $this->getUser()->lastname;
    }

    private function getClassSubjects()
    {
        $subjects = Subjects::findAll(['category' => [Utility::getStudentClassCategory($this->class_id ? $this->getCurrentClass()->global_class_id : $this->getCurrentClass()->id), 'all']]);
        return  $subjects ? $subjects : null;
    }

    private function getCurrentClassSubject()
    {
        if ($this->subject_id) {
            return Subjects::findOne([
                'category' => Utility::getStudentClassCategory($this->class_id ? $this->getCurrentClass()->global_class_id : $this->getCurrentClass()->id)
            ]);
        }

        return $this->getClassSubjects()[0] ? $this->getClassSubjects()[0] : null;
    }

    private function getDefaultClass()
    {
        if ($this->student_id) {
            $class_id = $this->getSchoolStudentClass($this->student_id);
        } else {
            $class_id = $this->getSchoolStudentClass(Yii::$app->user->id);
        }

        if (!$class_id) {
            $class_id =  User::find()
                        ->select(['class'])
                        ->where(['id' => $this->student_id ? $this->student_id : Yii::$app->user->id])
                        ->one();
        }

        return $this->getClass($class_id);
    }

    private function getClass($class_id)
    {
        return Classes::find()->where(['global_class_id' => $class_id])->one();
    }

    private function getSchoolStudentClass($student_id)
    {
        return StudentSchool::find()
                    ->select(['class_id'])
                    ->where(['student_id' => $student_id])
                    ->one();
    }

    private function getSubjects()
    {
        return Subjects::findAll(['id' => $this->getParticipatedSubjectsList()]);
    }

    private function getParticipatedSubjectsList()
    {
        return ArrayHelper::getColumn(
            QuizSummary::find()->where(['student_id' => $this->student_id])->groupBy('subject_id')->all(),
            'subject_id'
        );
    }

    private function getUser()
    {
        return User::findOne(['id' => $this->student_id]);
    }

    private function getCurrentSubject()
    {
        if ($this->subject_id) {
            return Subjects::findOne(['id' => $this->subject_id]);
        }

        return $this->getSubjects()[0];
    }

    private function getClasses()
    {
        $student_classes = ArrayHelper::getColumn(
            StudentSchool::findAll(['student_id' => $this->student_id]),
            'class_id'
        );

        return Classes::findAll(['id' => $student_classes]);
    }

    private function getGlobalClasses()
    {
        return GlobalClass::find()->where(['status' => 1])->all();
    }

    private function getClassTerms()
    {
        return [
            'first' => $this->getClassTermData('first'),
            'second' => $this->getClassTermData('second'),
            'third' => $this->getClassTermData('third'),
        ];
    }

    private function getClassTermData($term)
    {
        $start_index = $term . '_term_start';
        $end_index = $term . '_term_end';
        return [
            'start_date' => Yii::$app->params[$start_index],
            'end_date' => Yii::$app->params[$end_index],
            'topics' => $this->getTopicsInTerm($term),
        ];
    }

    private function getTopicsInTerm($term)
    {
        $topics = SubjectTopics::find()
            ->where([
                'subject_topics.subject_id' => $this->getCurrentSubject(),
                'subject_topics.term' => $term,
            ])
            ->all();

        $previous_class_topics = $this->getPreviousClassTopics();

        return array_merge($topics, $previous_class_topics);
    }

    private function getPreviousClassTopics()
    {
        $previous_class_topics = ArrayHelper::getColumn(
            StudentAdditiionalTopics::find()
                ->where([
                    'student_id' => $this->student_id,
                    'class_id' => $this->getCurrentClass(),
                    'status' => 1
                ])
                ->all(),
            'topic_id'
        );

        return SubjectTopics::find()->where(['id' => $previous_class_topics])->all();
    }

    private function getCurrentClass()
    {
        if ($this->class_id) {
            return Classes::findOne(['global_class_id' => $this->class_id]);
        }

        return $this->getDefaultClass();
    }

    private function getTopics()
    {
        return QuizSummary::find()
            ->where(['student_id' => $this->student_id])
            ->groupBy('topic_id');
    }

    private function getFirstTermReport()
    {
        $topics = ArrayHelper::getColumn($this->getTopics()->andWhere(['term' => 'first'])->all(), 'topic_id');
        $first_term_topics = SubjectTopics::find()
            ->select(['subject_topics.id AS topic_id', 'subject_topics.topic AS topic_name', 'subject_topics.image AS topic_image'])
            ->where(['id' => $topics])
            ->asArray()
            ->all();


        return [$first_term_topics, 'learning_area' => $this->getLearningArea($topics)];
    }

    private function getSecondTermReport()
    {
        $topics = ArrayHelper::getColumn($this->getTopics()->andWhere(['term' => 'second'])->all(), 'topic_id');
        $second_term_topics = SubjectTopics::find()
            ->select(['id', 'topic', 'image'])
            ->where(['id' => $topics])
            ->all();


        return [$second_term_topics, 'learning_area' => $this->getLearningArea($topics)];
    }

    private function getThirdTermReport()
    {
        $topics = ArrayHelper::getColumn($this->getTopics()->andWhere(['term' => 'third'])->all(), 'topic_id');
        $third_term_topics = SubjectTopics::find()
            ->select(['id', 'topic', 'image'])
            ->where(['id' => $topics])
            ->all();


        return [$third_term_topics, 'learning_area' => $this->getLearningArea($topics)];
    }

    private function getLearningArea($topics)
    {
        return LearningArea::findAll(['topic_id' => $topics]);
    }

    private function getReport()
    {
        return [
            'first' => $this->getFirstTermReport(),
            'second' => $this->getSecondTermReport(),
            'third' => $this->getThirdTermReport()
        ];
    }
}