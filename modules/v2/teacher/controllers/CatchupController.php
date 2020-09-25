<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\models\HomeworkQuestions;
use app\modules\v2\models\QuizSummaryDetails;
use app\modules\v2\models\SubjectTopics;
use Yii;
use app\modules\v2\models\{TutorSession,
    ApiResponse,
    TutorSessionTiming,
    TutorSessionParticipant,
    Classes,
    User,
    TeacherClassSubjects,
    TeacherClass,
    RecommendedResources,
    QuizSummary,
    Questions,
    Subjects,
    ProctorReport,
    Homeworks
};
use app\modules\v2\student\models\StartPracticeForm;
use app\modules\v2\components\SharedConstant;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};


class CatchupController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\TutorSession';
    private $students;

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];
        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
    }

    public function actionCreateSession()
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_TEACHER) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $type = Yii::$app->request->post('type');
        $student_id = Yii::$app->request->post('student_id');
        //$subject_id = Yii::$app->request->post('subject_id');
        $topic_id = Yii::$app->request->post('topic_id');
        //$day = Yii::$app->request->post('day');
        $time = Yii::$app->request->post('time');
        $date = Yii::$app->request->post('date');
        $class_id = Yii::$app->request->post('class_id');

        $topicObject = SubjectTopics::findOne(['id' => $topic_id]);
        $subject_id = isset($topicObject->subject_id) ? $topicObject->subject_id : null;

        $form = new \yii\base\DynamicModel(compact('type', 'student_id', 'subject_id', 'time', 'class_id', 'date', 'topic_id'));
        $form->addRule(['type', 'student_id', 'time', 'class_id', 'date', 'topic_id'], 'required');
        $form->addRule(['date'], 'date', ['format' => 'php:Y-m-d']);
        $form->addRule(['time'], 'time', ['format' => 'php:H:i:s']);
        $form->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id' => 'class_id']]);
        $form->addRule(['subject_id'], 'exist', ['targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['subject_id' => 'subject_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = new TutorSession;
        $model->requester_id = Yii::$app->user->id;
        $model->class = $class_id;
        $model->subject_id = $subject_id;
        //save topic_id. Session sometimes needs to be topic bases. Instead of subject.
        $model->meta = 'recommendation';
        $model->category = SharedConstant::TUTOR_SESSION_CATEGORY_TYPE[SharedConstant::VALUE_ZERO];
        $model->is_school = SharedConstant::VALUE_ONE;
        $model->availability = $date . ' ' . $time;
        if (Yii::$app->request->post('type') == 'single') {
            $model->student_id = Yii::$app->request->post('student_id');
        } else {
            $this->students = Yii::$app->request->post('student_id');
        }

        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Tutor session record not generated');
            }

            if ($this->students) {
                if (!$this->tutorSessionParticipant($this->students, $model->id)) {
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Tutor session record not generated');
                }
            }

            if (!$this->tutorSessionTiming($model->id)) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Tutor session record not generated');
            }


            $dbtransaction->commit();
        } catch (Exception $e) {
            $dbtransaction->rollBack();
            return (new ApiResponse)->error($e, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Tutor session record not generated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Tutor session record generated');
    }

    private function tutorSessionParticipant($students, $session_id)
    {
        foreach ($students as $student) {
            $model = new TutorSessionParticipant;
            $model->session_id = $session_id;
            $model->participant_id = $student;
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }

    private function tutorSessionTiming($session_id)
    {
        $model = new TutorSessionTiming;
        $model->session_id = $session_id;
        $model->day = Yii::$app->request->post('date');
        $model->time = Yii::$app->request->post('time');
        if (!$model->save()) {
            return false;
        }

        return true;
    }

    public function actionCreatePractice()
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_TEACHER) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $student_id = Yii::$app->request->post('student_id');
        $topic_ids = Yii::$app->request->post('topic_ids');
        $reference_type = Yii::$app->request->post('reference_type');
        $reference_id = Yii::$app->request->post('reference_id');
        $teacher_id = Yii::$app->user->id;
        $form = new \yii\base\DynamicModel(compact('student_id', 'topic_ids', 'teacher_id', 'reference_type', 'reference_id'));
        $form->addRule(['student_id', 'topic_ids', 'teacher_id', 'reference_type', 'reference_id'], 'required');
        $form->addRule(['reference_type'], 'in', ['range' => SharedConstant::REFERENCE_TYPE]);
        $form->addRule(['student_id'], 'exist', ['targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']]);
        $form->addRule(['teacher_id'], 'exist', ['targetClass' => User::className(), 'targetAttribute' => ['teacher_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = new StartPracticeForm;
        $model->topic_ids = $topic_ids;
        $model->type = SharedConstant::REFERENCE_TYPE[SharedConstant::VALUE_TWO];
        $model->reference_type = $reference_type;
        $model->reference_id = $reference_id;
        $model->practice_type = 'recommendation';
        if (!$homework_model = $model->initializePractice($student_id, $teacher_id)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice Initialization failed');
        }

        return (new ApiResponse)->success($homework_model, ApiResponse::SUCCESSFUL, 'Practice Initialization succeeded');
    }

    public function actionVideoRecommendation()
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_TEACHER) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $model = new RecommendedResources;
        $model->creator_id = Yii::$app->user->id;
        $model->receiver_id = Yii::$app->request->post('student_id');
        $model->resources_type = 'video';
        $model->resources_id = Yii::$app->request->post('resources_id');
        $model->reference_type = Yii::$app->request->post('reference_type');
        $model->reference_id = Yii::$app->request->post('reference_id');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video recommendation failed');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Video recommendation succeeded');
    }

    public function actionHomeworkSummaryProctor($student_id, $assessment_id)
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_TEACHER) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $teacher_id = Yii::$app->user->id;
        $form = new \yii\base\DynamicModel(compact('student_id', 'assessment_id', 'teacher_id'));
        $form->addRule(['student_id', 'assessment_id'], 'required');
        $form->addRule(['student_id'], 'exist', ['targetClass' => User::className(), 'targetAttribute' => ['student_id' => 'id']]);
        $form->addRule(['assessment_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['assessment_id' => 'id', 'teacher_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = QuizSummary::find()
            ->alias('qs')
            ->innerJoin('homeworks h', "h.id = qs.homework_id AND h.type = 'homework'")
            ->where([
                'qs.student_id' => $student_id,
                'qs.homework_id' => $assessment_id,
                'qs.submit' => SharedConstant::VALUE_ONE
            ])->one();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework Summary Proctor not found');
        }

        $homework = Homeworks::find()
            ->select(['title', 'tag', 'created_at', 'close_date'])
            ->where(['id' => $assessment_id, 'teacher_id' => Yii::$app->user->id])
            ->asArray()->one();

        $homeworkQuestions = ArrayHelper::getColumn(HomeworkQuestions::find()->where(['homework_id' => $assessment_id])->all(), 'question_id');

        $questions = Questions::find()
            ->select(['id', 'question', 'answer', 'image', 'type'])
            ->where(['id' => $homeworkQuestions])->asArray()->all();
        $allQuestions = [];
        foreach ($questions as $question) {
            $quizDetails = QuizSummaryDetails::findOne(['quiz_id' => $model->id, 'homework_id' => $assessment_id, 'question_id' => $question['id']]);
            $correctStatus = $quizDetails->selected == $question['answer'];
            $selected = $quizDetails->selected;
            $allQuestions[] = array_merge(ArrayHelper::toArray($question), ['correctStatus' => $correctStatus, 'selected' => $selected]);
        }

        $data = [
            'score' => ($model->correct / count($homeworkQuestions)) * 100,
            'correct' => $model->correct,
            'incorrect' => $model->failed,
            'skipped' => $model->skipped,
            'datetime' => $model->submit_at,
            'homework' => $homework,
            'subject' => Subjects::findOne(['id' => $model->subject_id]),
            'assessment_type' => $model->type,
            'questions' => $allQuestions,
            'proctor' => ProctorReport::findOne(['assessment_id' => $assessment_id, 'student_id' => $student_id])];

        return (new ApiResponse)->success(
            $data,
            ApiResponse::SUCCESSFUL, 'Homework Summary Proctor found');
    }
}

