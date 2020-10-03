<?php

namespace app\modules\v2\models;

use app\modules\v2\components\InputNotification;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tutor_session".
 *
 * @property int $id
 * @property int $requester_id
 * @property int|null $student_id
 * @property string|null $title
 * @property string $repetition
 * @property int|null $class
 * @property int|null $subject_id
 * @property int $session_count
 * @property int|null $curriculum_id
 * @property string $category Either paid, free, covid19 or class
 * @property string|null $availability
 * @property int $is_school
 * @property string|null $preferred_client
 * @property string|null $meeting_token For daily.co, this is used to set the host
 * @property string|null $meeting_room this is use to determine the room for this class
 * @property string|null $meta Any additional data
 * @property string $status
 * @property string $created_at
 *
 * @property ClassAttendance[] $classAttendances
 * @property TutorSessionTiming[] $tutorSessionTimings
 */
class TutorSession extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    private $new_sessions = [];
    public $class_id;

    public static function tableName()
    {
        return 'tutor_session';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['requester_id', 'category'], 'required'],
            [['requester_id', 'student_id', 'class', 'subject_id', 'session_count', 'curriculum_id', 'is_school'], 'integer'],
            [['repetition', 'preferred_client', 'meeting_token', 'meta', 'status'], 'string'],
            [['availability', 'created_at'], 'safe'],
            [['title'], 'string', 'max' => 200],
            [['category'], 'string', 'max' => 50],
            [['meeting_room'], 'string', 'max' => 255],

            [['requester_id', 'class_id', 'subject_id', 'repetition', 'category', 'availability', 'title'], 'required', 'on' => 'new-class'],
            ['subject_id', 'exist', 'targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['subject_id']],
            ['class_id', 'exist', 'targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id' => 'class_id']],
            ['repetition', 'in', 'range' => ['once', 'daily', 'workdays', 'weekly']],
            ['availability', 'datetime', 'format' => 'php:Y-m-d H:i:s']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'requester_id' => 'Requester ID',
            'student_id' => 'Student ID',
            'title' => 'Title',
            'repetition' => 'Repetition',
            'class' => 'Class',
            'subject_id' => 'Subject ID',
            'session_count' => 'Session Count',
            'curriculum_id' => 'Curriculum ID',
            'category' => 'Category',
            'availability' => 'Availability',
            'is_school' => 'Is School',
            'preferred_client' => 'Preferred Client',
            'meeting_token' => 'Meeting Token',
            'meeting_room' => 'Meeting Room',
            'meta' => 'Meta',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'requester_id',
            'student_id',
            'title',
            'repetition',
            'class',
            'subject_id',
            'session_count',
            'curriculum_id',
            'category',
            'availability',
            'is_school',
            'preferred_client',
            'meeting_token',
            'meeting_room',
            'meta',
            'status',
            'tutorSessionTiming',
            'tutorSessionParticipant',
        ];
    }

    public function scheduleClass($model)
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {

            if (!$model->save()) {
                return false;
            }

            if (!$feed = $this->addFeed($model->id)) {
                return false;
            }


            /**
             * Send google calendar notification
             *
             * date_default_timezone_set("Africa/Lagos");
             * $start = date(DATE_ATOM, strtotime($model->availability));
             * $end = date(DATE_ATOM, strtotime("+45 minutes", strtotime($model->availability)));
             * $invitees = [];
             *
             * $invitees[] = ['email' => Yii::$app->user->identity->email];
             *
             * //parents email
             * $studentIds = ArrayHelper::getColumn($model->classes->studentSchool, 'student_id');
             * $parentIDs = Parents::find()->where(['student_id' => $studentIds, 'status' => 1])
             * ->groupBy(['parent_id'])
             * ->asArray()
             * ->all();
             * $parents = User::find()->where(['id' => ArrayHelper::getColumn($parentIDs, 'parent_id'), 'type' => 'parent'])
             * ->all();
             *
             * foreach ($parents as $parent) {
             * $invitees[] = ['email' => $parent->email];
             * }
             *
             * $google = new GoogleCalendar();
             * $google->createEvent($model->title, $start, $end, $invitees);
             * $this->SendLessonNotification($model, $parents, $studentIds);
             */


            $dbtransaction->commit();
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }

        return $model;

    }

    public function getNewSessions()
    {

        if (Yii::$app->user->identity->type == 'teacher') {
            $condition = ['requester_id' => Yii::$app->user->id, 'status' => 'pending'];
        } elseif (Yii::$app->user->identity->type == 'school') {
            $classes = ArrayHelper::getColumn(Classes::find()
                ->where(['school_id' => Utility::getSchoolAccess()])->all(), 'id');

            $condition = ['class' => $classes, 'status' => 'pending'];
        }elseif (Yii::$app->user->identity->type == 'student'){

            if ($studentModel = StudentSchool::find()
                ->where(['student_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE])->one())
                $student_class = ArrayHelper::getColumn($studentModel, 'class_id');
            else
                $student_class = null;

            $condition = ['class' => $student_class];
        }elseif (Yii::$app->user->identity->type == 'parent'){

            $studentIDs = ArrayHelper::getColumn(Parents::find()->where(['parent_id' => Yii::$app->user->id])->all(), 'student_id');

            $studentClass = StudentSchool::find()->where(['student_id' => $studentIDs]);
            if (isset($_GET['class_id']))
                $studentClass = $studentClass->andWhere(['class_id' => $_GET['class_id']]);


            $studentClass = $studentClass->andWhere(['status' => SharedConstant::VALUE_ONE])->all();
            $student_class = ArrayHelper::getColumn($studentClass, 'class_id');

            $condition = ['class' => $student_class];
        }

        $sessions = parent::find()
            ->where(['AND', $condition, ['is_school' => 1]])
            ->andWhere(['>', 'availability', date("Y-m-d")])
            ->andWhere(['<>', 'status', 'completed'])
            ->orderBy(['availability' => SORT_ASC])
            ->all();
        foreach ($sessions as $session) {
            //strtotime($session->availability) <= time() + 604800 &&
            if (strtotime($session->availability) >= time()) {
                array_push($this->new_sessions, [
                    'id' => $session->id,
                    'type' => 'live_class',
                    'title' => $session->title,
                    'date_time' => $session->availability,
                ]);
            }
        }

        return $this->new_sessions;
    }

    public function addFeed($homework_id)
    {
        $model = new Feed;
        $model->type = 'live_class';
        $model->class_id = $this->class_id;
        $model->view_by = 'all';
        $model->user_id = Yii::$app->user->id;
        $model->reference_id = $homework_id;
        if (!$model->save(false)) {
            return false;
        }

        return $model;
    }

    public function getClasses()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class']);
    }

    public function getTutorSessionTiming()
    {
        return $this->hasOne(TutorSessionTiming::className(), ['session_id' => 'id']);
    }

    public function getTutorSessionParticipant()
    {
        return $this->hasMany(TutorSessionParticipant::className(), ['session_id' => 'id']);
    }
}
