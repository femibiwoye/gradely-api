<?php

namespace app\modules\v2\models;

use app\modules\v2\components\Utility;
use Yii;
use app\modules\v2\components\SharedConstant;
use yii\behaviors\SluggableBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "homeworks".
 *
 * @property int $id
 * @property int|null $student_id
 * @property int|null $teacher_id
 * @property int $subject_id
 * @property int|null $class_id
 * @property int|null $school_id
 * @property int|null $exam_type_id It is null by default but the curriculum should be update at the point of updating questions or publishing practice.
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property int|null $topic_id
 * @property int|null $curriculum_id
 * @property int $publish_status
 * @property string|null $publish_at
 * @property string $access_status
 * @property string|null $open_date
 * @property string|null $close_date
 * @property int|null $duration Duration should be in minutes
 * @property string $type Homework is students regular take home. Practice is students self created assessment. Diagnostic is an auto-generated assessment to know the level of the child. Recommendation is a suggested/recommended practice/material/videos to help improve their level of knowledge. Catchup is a gamified practice. Lesson is a material created by teacher for student to learn.
 * @property string|null $tag Tag is used to identify homework sub category. Maybe it is an homework, quiz or exam
 * @property int $status
 * @property string $created_at
 * @property string $mode
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property int|null $is_custom_topic
 * @property int|null $is_proctor
 * @property int $selected_student
 *
 * @property HomeworkQuestions[] $homeworkQuestions
 * @property PracticeTopics[] $practiceTopics
 * @property ProctorReport[] $proctorReports
 */
class Homeworks extends \yii\db\ActiveRecord
{
    private $homework_annoucements = [];
    private $sum_of_correct_answers;
    private $sum_of_attempts;
    private $total = SharedConstant::VALUE_ZERO;
    private $struggling_students = SharedConstant::VALUE_ZERO;
    private $average_students = SharedConstant::VALUE_ZERO;
    private $excellent_students = SharedConstant::VALUE_ZERO;


    public static function tableName()
    {
        return 'homeworks';
    }

    public function behaviors()
    {
        return [
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'title',
                'ensureUnique' => true,
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'subject_id', 'class_id', 'school_id', 'slug', 'title'], 'required', 'on' => 'assessment'],
            [['teacher_id', 'subject_id', 'class_id', 'school_id', 'exam_type_id', 'topic_id', 'curriculum_id', 'publish_status', 'duration', 'status', 'exam_type_id', 'selected_student','is_custom_topic','is_proctor'], 'integer'],
            [['description', 'access_status', 'type', 'tag','mode'], 'string'],
            [['open_date', 'close_date', 'created_at','publish_at','is_custom_topic'], 'safe'],
            [['slug', 'title'], 'string', 'max' => 255],
            [['student_id', 'subject_id', 'class_id', 'slug', 'title'], 'required', 'on' => 'student-practice'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'teacher_id' => 'Teacher ID',
            'subject_id' => 'Subject ID',
            'class_id' => 'Class ID',
            'school_id' => 'School ID',
            'exam_type_id' => 'Exam Type ID',
            'slug' => 'Slug',
            'title' => 'Title',
            'description' => 'Description',
            'topic_id' => 'Topic ID',
            'curriculum_id' => 'Curriculum ID',
            'publish_status' => 'Publish Status',
            'access_status' => 'Access Status',
            'open_date' => 'Open Date',
            'close_date' => 'Close Date',
            'duration' => 'Duration',
            'status' => 'Status',
            'created_at' => 'Created At',
            'reference_type' => 'reference_type',
            'reference_id' => 'reference_id',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'title',
            'description',
            'subject',
            'teacher',
            'class_id',
            'class',
            'school_id',
            'exam_type_id',
            'slug',
            'open_date',
            'close_date',
            'is_taken' => 'isTaken',
            'is_closed' => 'isClosed',
            'questionCount',
            'duration',
            'is_proctor',
            'questionsDuration',
            'score',
            'status',
            'tag',
            'activeStatus' => 'statusMessage', //this is used for student to know if homework is open, expired or closed
            'expiry_status' => 'expiryStatus',
            'publish_status' => 'publishStatus',
            'topics',
            'attachments',
            'average',
            'expected_students' => 'studentExpectedCount',
            'submitted_students' => 'studentsSubmitted',
            'completion' => 'completedRate',
            'selected_students' => 'selectedStudents',
            'created_at'
            //'has_question' => 'homeworkHasQuestion'
//            'questions' => 'homeworkQuestions',
//            'homework_performance' => 'homeworkPerformance'
        ];
    }

//    public function extraFields()
//    {
//        return ['teacher', ];
//    }

    public static function find()
    {
        return parent::find()->where(['homeworks.status' => 1]);
    }

    public function getHomeworkSummary()
    {
        return [
            'average' => $this->average,
            'completion_rate' => $this->completedRate,
            'expected_students' => $this->studentExpected,
            'submitted_students' => $this->studentsSubmitted,
            'struggling_students' => $this->strugglingStudents,
            'average_students' => $this->averageStudents,
            'excellence_students' => $this->excellentStudents,

        ];
    }

    public function getIsTaken()
    {
        if (Yii::$app->user->identity->type == 'parent')
            $childID = Yii::$app->request->get('class_id'); // class_id is used for child_id in feed
        else
            $childID = Yii::$app->user->id;
        if (QuizSummary::find()->where(['homework_id' => $this->id, 'student_id' => $childID, 'submit' => SharedConstant::VALUE_ONE])->exists()) {
            return 1;
        }

        return 0;
    }

    public function getIsClosed()
    {
        if ($this->getExpiryStatus() == 'closed') {
            return 1;
        } else {
            return 0;
        }
    }

    public function getQuestionsDuration()
    {
        return HomeworkQuestions::find()->where(['homework_id' => $this->id])->sum('duration');
    }

    public function getCompletedRate()
    {
        $completed = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->count();

        return $this->getStudentExpectedCount() > 0 ? ($completed / $this->getStudentExpectedCount()) * 100 : 0;
    }

    public function getStudentExpectedCount()
    {
        return StudentSchool::find()
            ->where(['class_id' => $this->class_id, 'school_id' => $this->school_id, 'status' => 1, 'is_active_class' => 1])->count();
    }

    public function getStudentsSubmitted()
    {
        return QuizSummary::find()
            ->innerJoin('student_school', 'student_school.student_id = quiz_summary.student_id AND student_school.class_id = ' . $this->class_id . ' AND student_school.status = 1')
            ->where(['homework_id' => $this->id, 'submit' => SharedConstant::VALUE_ONE, 'quiz_summary.class_id' => $this->class_id])->count();
    }

    public function getSelectedStudents()
    {
        return $this->hasMany(HomeworkSelectedStudent::className(), ['homework_id' => 'id']);
    }

    public function getStrugglingStudents()
    {
        $models = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all();
        foreach ($models as $model) {
            $marks = ($model->correct / $model->total_questions) * 100;
            if ($marks < 50) {
                $this->struggling_students = $this->struggling_students + 1;
            }
        }

        return $this->struggling_students;
    }

    public function getAverageStudents()
    {
        $models = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all();
        foreach ($models as $model) {
            $marks = ($model->correct / $model->total_questions) * 100;
            if ($marks >= 50 && $marks <= 75) {
                $this->average_students = $this->average_students + 1;
            }
        }

        return $this->average_students;
    }

    public function getExcellentStudents()
    {
        $models = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all();
        foreach ($models as $model) {
            $marks = ($model->correct / $model->total_questions) * 100;
            if ($marks > 75) {
                $this->excellent_students = $this->excellent_students + 1;
            }
        }

        return $this->excellent_students;
    }

    public function getAverage()
    {
        //getting those records which are submitted
        $quizSummaries = $this->getQuizSummaryRecord()->where(['submit' => SharedConstant::VALUE_ONE])->all();
        if (!$quizSummaries) {
            return SharedConstant::VALUE_ZERO;
        }

        foreach ($quizSummaries as $quizSummary) {
            $this->sum_of_correct_answers = $this->sum_of_correct_answers + $quizSummary->correct;
        }

        return $quizSummaries[0]->total_questions > 0 ? (double)round(($this->sum_of_correct_answers / $quizSummaries[0]->total_questions) * 100) : 0;
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
            ->andWhere('quiz_summary_details.selected <> quiz_summary_details.answer')
            ->all();
    }

    public function getCorrectStudents($question_id)
    {
        return User::find()
            ->innerJoin('quiz_summary_details', 'user.id = quiz_summary_details.student_id')
            ->where(['user.type' => SharedConstant::ACCOUNT_TYPE[3], 'quiz_summary_details.question_id' => $question_id])
            ->andWhere('quiz_summary_details.selected = quiz_summary_details.answer')
            ->all();
    }

    /*public function getCompletion()
    {
        $quizSummaryRecords = $this->getQuizSummaryRecord()->andWhere(['submit'=>SharedConstant::VALUE_ONE])->all();
        if (!$quizSummaryRecords) {
            return SharedConstant::VALUE_ZERO;
        }


        foreach ($quizSummaryRecords as $quizSummaryRecord) {
            if ($quizSummaryRecord->submit = SharedConstant::VALUE_ONE) {
                $this->sum_of_attempts = $this->sum_of_attempts + SharedConstant::VALUE_ONE;
            }
        }

        return ($this->sum_of_attempts / count($quizSummaryRecords)) * 100;
    }*/

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

    public function getHomeworkHasQuestion()
    {
        return HomeworkQuestions::find()->where(['homework_id' => $this->id])->exists() ? 1 : 0;
    }

    public function getSubject()
    {
        $model= $this->hasOne(Subjects::className(), ['id' => 'subject_id']);

        $model = $model->select(['subjects.id','subjects.slug','subjects.description',"IFNULL(ss.custom_subject_name,subjects.name) as name"])
            ->leftJoin('school_subject ss','ss.subject_id = subjects.id')
                ->where(['ss.status' => 1,'ss.school_id'=>$this->school_id])
            ;
        return $model;
    }

    public function getQuizSummary()
    {
        return QuizSummary::find()->where(['student_id' => $this->student_id])
            ->orWhere(['teacher_id' => Yii::$app->user->id])
            //->andWhere(['subject_id' => $this->subject->id])
            ->andWhere(['homework_id' => $this->id])->one();

    }

    public function getScore()
    {
        if (!$this->quizSummary) {
            return null;
        }

        return round($this->quizSummary->score);
    }

    public function getQuestionCount()
    {
        return HomeworkQuestions::find()->where(['homework_id' => $this->id])->count();
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
            $condition = Yii::$app->request->get('class') ? ['teacher_id' => Yii::$app->user->id, 'class_id' => Yii::$app->request->get('class')] : ['teacher_id' => Yii::$app->user->id];
        } elseif (Yii::$app->user->identity->type == 'school') {
            $classes = ArrayHelper::getColumn(Classes::find()
                ->where(['school_id' => Utility::getSchoolAccess()])->all(), 'id');
            $condition = Yii::$app->request->get('class') ? ['class_id' => Yii::$app->request->get('class')] : ['class_id' => $classes];
        } elseif (Yii::$app->user->identity->type == 'student') {
            $studentIDs = Yii::$app->user->id;
            if ($studentModel = StudentSchool::find()
                ->where(['student_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE])->one())
                $student_class = $studentModel->class_id;
            else
                $student_class = null;

            $condition = ['class_id' => $student_class];
            $studentCheck = true;
        } elseif (Yii::$app->user->identity->type == 'parent') {
            $studentIDs = ArrayHelper::getColumn(Parents::find()->where(['parent_id' => Yii::$app->user->id, 'status' => 1, 'student_id' => Yii::$app->request->get('child')])->all(), 'student_id');

            $studentClass = StudentSchool::find()->where(['student_id' => $studentIDs, 'status' => 1, 'is_active_class' => 1]);
            if (isset($_GET['class_id']))
                $studentClass = $studentClass->andWhere(['class_id' => $_GET['class_id']]);

            $studentClass = $studentClass->andWhere(['status' => SharedConstant::VALUE_ONE])->all();
            $student_class = ArrayHelper::getColumn($studentClass, 'class_id');

            $condition = ['class_id' => $student_class];
            $studentCheck = true;
        }


        $homeworks = parent::find()->where(['AND', $condition, ['publish_status' => 1, 'status' => 1, 'type' => 'homework']]);
        if (Yii::$app->user->identity->type == 'parent' || Yii::$app->user->identity->type == 'student') {
            $homeworks = $homeworks->leftjoin('homework_selected_student hss', "hss.homework_id = homeworks.id")->andWhere(['OR', ['homeworks.selected_student' => 1, 'hss.student_id' => Utility::getParentChildID()], ['homeworks.selected_student' => 0]]);
        }
        $homeworks = $homeworks->andWhere(['>', 'close_date', date("Y-m-d")])
            ->orderBy(['close_date' => SORT_ASC])
            ->all();

        foreach ($homeworks as $homework) {
            if (strtotime($homework->open_date) <= time() + 604800 && strtotime($homework->close_date) >= time()) {
                if (isset($studentCheck) && QuizSummary::find()->where(['student_id' => $studentIDs, 'type' => 'homework', 'homework_id' => $homework->id])->exists()) {
                    continue;
                }
                array_push($this->homework_annoucements, [
                    'id' => $homework->id,
                    'type' => $homework->type,
                    'title' => $homework->title,
                    'date_time' => $homework->close_date,
                    'reference' => $homework
                ]);
            }
        }

        return $this->homework_annoucements;
    }

    public function getQuizSummaryRecord()
    {
        return $this->hasMany(QuizSummary::className(), ['homework_id' => 'id']);
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

    public function getTeacher()
    {
        return $this->hasOne(User::className(), ['id' => 'teacher_id']);
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

    public function getClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id'])->select(['id', 'class_name']);
    }

}
