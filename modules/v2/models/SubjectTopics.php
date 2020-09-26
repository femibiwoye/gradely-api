<?php

namespace app\modules\v2\models;

use app\modules\v1\models\QuizSummary;
use app\modules\v2\components\Utility;
use Yii;
use yii\helpers\ArrayHelper;
use app\modules\v2\components\{SharedConstant};

/**
 * This is the model class for table "subject_topics".
 *
 * @property int $id
 * @property int $subject_id
 * @property int|null $creator_id
 * @property int|null $class_id
 * @property int|null $school_id
 * @property string $slug
 * @property string $topic
 * @property string $description
 * @property int $week_number It contains numbers. 1 stands for week one, 5 stands for week 5
 * @property string $term
 * @property int $exam_type_id
 * @property string|null $image
 * @property int $status
 * @property string $created_at
 *
 * @property CatchupTopics[] $catchupTopics
 * @property PracticeTopics[] $practiceTopics
 * @property VideoAssign[] $videoAssigns
 */
class SubjectTopics extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    private $struggling = [];
    private $average = [];
    private $excellence = [];
    public static function tableName()
    {
        return 'subject_topics';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['subject_id', 'slug', 'topic', 'description', 'week_number', 'term', 'exam_type_id'], 'required'],
            [['subject_id', 'creator_id', 'class_id', 'school_id', 'week_number', 'exam_type_id', 'status'], 'integer'],
            [['description', 'term'], 'string'],
            [['created_at'], 'safe'],
            [['slug', 'topic'], 'string', 'max' => 200],
            [['image'], 'string', 'max' => 255],
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['image'] = function ($model) {
            return Utility::AbsoluteImage($model->image,'topics');
        };
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'subject_id' => 'Subject ID',
            'creator_id' => 'Creator ID',
            'class_id' => 'Class ID',
            'school_id' => 'School ID',
            'slug' => 'Slug',
            'topic' => 'Topic',
            'description' => 'Description',
            'week_number' => 'Week Number',
            'term' => 'Term',
            'exam_type_id' => 'Exam Type ID',
            'image' => 'Image',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }

    public function getPerformance()
    {
        return [
            'id' => $this->id,
            'title' => $this->topic,
            'description' => $this->description,
            'week_number' => $this->week_number,
            'term' => $this->term,
            'exam_type_id' => $this->exam_type_id,
            'status' => $this->status,
            'struggling' => $this->strugglingStudents,
            'average' => $this->averageStudents,
            'excellence' => $this->excellenceStudents,
        ];
    }

    public function getStrugglingStudents()
    {
        return $this->struggling;
    }

    public function getAverageStudents()
    {
        return $this->average;
    }

    public function getExcellentStudents()
    {
        return $this->excellence;
    }

    public function getStudentsInClass()
    {
        return ArrayHelper::getColumn(
            StudentSchool::find()
                ->select(['student_id'])
                ->where(['class_id' => Yii::$app->request->get('class_id')])
                ->all(),
            'student_id'
        );
    }

    public function getScore()
    {
        foreach ($this->studentsInClass as $student) {
            $score = $this->getResult($student, $this->id);
            if ($score > 75) {
                $this->excellence = array_merge(ArrayHelper::toArray($this->getStudent($student, $score)), ['score' => $score ." %"]);
            } else if ($score >= 50 && $score < 75) {
                $this->average = array_merge(ArrayHelper::toArray($this->getStudent($student, $score)), ['score' => $score ." %"]);
            } else {
                $this->struggling = array_merge(ArrayHelper::toArray($this->getStudent($student, $score)), ['score' => $score ." %"]);
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->topic,
            'description' => $this->description,
            'week_number' => $this->week_number,
            'term' => $this->term,
            'exam_type_id' => $this->exam_type_id,
            'status' => $this->status,
            'struggling' => $this->struggling,
            'average' => $this->average,
            'excellence' => $this->excellence,
        ];
    }

    public function getStudent($student_id, $score)
    {
        return User::find()
                ->select('id, firstname, image')
                ->where(['id' => $student_id, 'type' => SharedConstant::ACCOUNT_TYPE[3]])
                ->all();
    }

    public function getResult($student_id, $topic_id)
    {
        $query = QuizSummaryDetails::find()
                    ->innerJoin('quiz_summary', 'quiz_summary.id = quiz_summary_details.quiz_id')
                    ->where(['quiz_summary.type' => SharedConstant::QUIZ_SUMMARY_TYPE[0]])
                    ->andWhere(['quiz_summary_details.student_id' => $student_id, 'quiz_summary_details.topic_id' => 18]);
        
        if (!$query->all()) {
            return SharedConstant::VALUE_ZERO;
        }

        $total_attempts = count($query->all());
        $total_correct = count($query->andWhere('quiz_summary_details.selected = quiz_summary_details.answer')->all());

        return ($total_correct / $total_attempts) * 100;

    }

    /**
     * Gets query for [[CatchupTopics]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCatchupTopics()
    {
        return $this->hasMany(CatchupTopics::className(), ['topic_id' => 'id']);
    }

    /**
     * Gets query for [[PracticeTopics]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPracticeTopics()
    {
        return $this->hasMany(PracticeTopics::className(), ['topic_id' => 'id']);
    }

    /**
     * Gets query for [[VideoAssigns]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVideoAssigns()
    {
        return $this->hasMany(VideoAssign::className(), ['topic_id' => 'id']);
    }

    public function getTopicPerformanceByID($id,$studentID)
    {
        $model = QuizSummary::find()->where(['topic_id'=>$id,'student_id'=>$studentID]);
        if($model->sum('correct')>0) {
            return $model->sum('correct') / $model->sum('total_questions') * 100;
        }else{
            return 0;
        }
    }
}
