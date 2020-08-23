<?php

namespace app\modules\v2\models;

use app\modules\v2\components\InputNotification;
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
            ['availability', 'datetime', 'format' => 'php:Y-m-d H:i']
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

    public function scheduleClass($model)
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {

            if (!$model->save()) {
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
        }

        $sessions = parent::find()
            ->where(['AND', $condition, ['is_school' => 1]])
            ->andWhere(['>', 'availability', date("Y-m-d")])
            ->orderBy(['availability' => SORT_ASC])
            ->all();
        foreach ($sessions as $session) {
            if (strtotime($session->availability) <= time() + 604800 && strtotime($session->availability) >= time()) {
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

    public function getClasses()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class']);
    }
}
