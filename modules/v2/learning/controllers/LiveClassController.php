<?php

namespace app\modules\v2\learning\controllers;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\ClassAttendance;
use app\modules\v2\models\GenerateString;
use app\modules\v2\models\PracticeMaterial;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\TutorSession;
use SebastianBergmann\CodeCoverage\Util;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use app\modules\v2\models\TutorSessionParticipant;

use app\modules\v2\components\UserJwt;

use Yii;

/**
 * Default controller for the `learning` module
 */
class LiveClassController extends Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];

        //$behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'except' => ['update-live-class-video'],
        ];

        return $behaviors;
    }

    private function classAttendance($session_id, $userID, $type, $token)
    {
        if ($classAttendance = ClassAttendance::find()->where(['session_id' => $session_id, 'user_id' => $userID])->one()) {
            $classAttendance->joined_updated = date('Y-m-d H:i:s');
        } else {
            $classAttendance = new ClassAttendance();
            $classAttendance->session_id = $session_id;
            $classAttendance->user_id = $userID;
            $classAttendance->type = $type;
            $classAttendance->token = $token;
        }
        if (!$classAttendance->save())
            return false;
        return true;
    }

    private function getPayload($user, $room)
    {
        $teacherName = $user->firstname . ' ' . $user->lastname;
        $image = Utility::ProfileImage($user->image);

        /// To change class name, add #config.subject="" to the end of the token
        /// #config.subject=%22This%20is%20new%20name%22
        /// e.g https://class.gradely.ng/dgo8wbrxed5fb710a1bgbul0rkmkw6?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJjb250ZXh0Ijp7InVzZXIiOnsiYXZhdGFyIjoiaHR0cHM6XC9cL3Jlcy5jbG91ZGluYXJ5LmNvbVwvZ3JhZGVseVwvaW1hZ2VcL3VwbG9hZFwvdjE2MDAzNDAyOTNcL3VzZXJcL2hjaGRpc2dqZnA0dzBhaGhkemJpLnBuZyIsIm5hbWUiOiJIYW51bWFuIGhhbnVtYW4iLCJlbWFpbCI6ImhhbnVtYW5AZ21haWwuY29tIn19LCJhdWQiOiJncmFkZWx5IiwiaXNzIjoiZ3JhZGVseSIsInN1YiI6ImNsYXNzLmdyYWRlbHkubmciLCJyb29tIjoiZGdvOHdicnhlZDVmYjcxMGExYmdidWwwcmtta3c2In0.2YoIO-1p4tD2uc0h7uHrPBIl1-TTPwpe0XCZm0ogM0o#config.subject=%22This%20is%20new%20name%22

        $payload = [
            "context" => [
                "user" => [
                    "avatar" => "$image",
                    "name" => "$teacherName",
                    "email" => "$user->email"
                ]
            ],
            "aud" => Yii::$app->params['appName'],
            "iss" => Yii::$app->params['appName'],
            "sub" => "class.gradely.ng",
            "room" => "{$room}"
        ];


        return $payload;
    }

    /**
     * The host starts the meeting.
     * @return ApiResponse
     * @throws \Exception
     */
    public function actionStartClass()
    {
        if (Yii::$app->user->identity->type != 'teacher')
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user');

        $session_id = Yii::$app->request->post('session_id');
        $requester_id = Yii::$app->user->id;
        $form = new \yii\base\DynamicModel(compact('session_id', 'requester_id'));
        $form->addRule(['session_id'], 'required')
            ->addRule(['session_id'], 'exist', ['targetClass' => TutorSession::className(), 'targetAttribute' => ['requester_id', 'session_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }


        $tutor_session = TutorSession::findOne(['id' => $session_id, 'requester_id' => $requester_id]);
        if (empty($tutor_session->meeting_room)) {
            $tutor_session->meeting_room = GenerateString::widget();
        }
        $tutor_session->status = 'ongoing';
        $payload = $this->getPayload(Yii::$app->user->identity, $tutor_session->meeting_room);
        $token = UserJwt::encode($payload, Yii::$app->params['live_class_secret_token']);
        $this->classAttendance($session_id, $requester_id, SharedConstant::LIVE_CLASS_USER_TYPE[0], $token);
        $tutor_session->meeting_token = $token;
        if(!$tutor_session->save())
            return (new ApiResponse)->error($tutor_session->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save your session');
         (new ApiResponse)->success($token, ApiResponse::SUCCESSFUL);
    }

    /**
     * Participant or student joining a class
     *
     * @return ApiResponse
     */
    public function actionJoinClass()
    {

        $session_id = Yii::$app->request->post('session_id');
        $user_id = Yii::$app->user->id;
        $form = new \yii\base\DynamicModel(compact('session_id'));
        $form->addRule(['session_id'], 'required')
            ->addRule(['session_id'], 'exist', ['targetClass' => TutorSession::className(), 'targetAttribute' => ['session_id' => 'id']]);
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (Yii::$app->user->identity->type != 'student')
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user');

        $student = TutorSessionParticipant::find()->where(['session_id' => $session_id, 'participant_id' => $user_id])->exists();

        $tutor_session = TutorSession::findOne(['id' => $session_id]);
        $schoolStudent = StudentSchool::find()->where(['student_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE, 'class_id' => $tutor_session->class])->exists();

        if (!$student && !$schoolStudent)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You are either not in class or not a participant');


        if ($tutor_session->status == 'pending') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher has not started');
        } elseif ($tutor_session->status == 'completed') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class has ended!');
        } elseif ($tutor_session->status == 'ongoing') {
            $payload = $this->getPayload(Yii::$app->user->identity, $tutor_session->meeting_room);
            $token = UserJwt::encode($payload, Yii::$app->params['live_class_secret_token']);
            $this->classAttendance($session_id, $user_id, SharedConstant::LIVE_CLASS_USER_TYPE[1], $token);
            return (new ApiResponse)->success($token, ApiResponse::SUCCESSFUL);
        } else {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid class status');
        }

    }

    /**
     * Ended class
     * @param $id
     * @return ApiResponse
     */
    public function actionEndClass()
    {
        $session_id = Yii::$app->request->post('session_id');
        $user_id = Yii::$app->user->id;
        $form = new \yii\base\DynamicModel(compact('session_id', 'user_id'));
        $form->addRule(['session_id'], 'required')
            ->addRule(['session_id'], 'exist', ['targetClass' => ClassAttendance::className(), 'targetAttribute' => ['session_id', 'user_id']]);
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $tutor_session = TutorSession::findOne(['id' => $session_id]);

        if (!$tutor_session)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid session');

        $attendance = ClassAttendance::find()->where(['user_id' => $user_id, 'session_id' => $session_id])->one();
        if (!empty($attendance->ended_at))
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Meeting already ended');


        if ($tutor_session->requester_id == $user_id) {
            $tutor_session->status = 'completed';
            $tutor_session->save();
        }


        if ($attendance) {
            $attendance->ended_at = date('Y-m-d H:i:s');
            $attendance->save();
        }
        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Class ended');
    }


    /**
     * This receives the video when meeting ends
     * @param $filename
     * @return ApiResponse
     */
    public function actionUpdateLiveClassVideo($filename)
    {

        $model = new \yii\base\DynamicModel(compact('filename'));
        $model->addRule(['filename'], 'required');
        //->addRule(['filename'], 'exist', ['targetClass' => TutorSession::className(), 'targetAttribute' => ['filename' => 'meeting_room']]);
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }
        $meeting_room = strtok($filename, '_');

        if (TutorSession::find()->where(['meeting_room' => $meeting_room])->exists() && !PracticeMaterial::find()->where(['filename' => $filename])->exists()) {
            $tutorSession = TutorSession::find()->where(['meeting_room' => $meeting_room])->one();
            $model = new PracticeMaterial();
            $model->user_id = $tutorSession->requester_id;
            $model->type = SharedConstant::FEED_TYPE;
            $model->tag = 'live_class';
            $model->filetype = SharedConstant::TYPE_VIDEO;
            $model->title = $tutorSession->title;
            $model->filename = $filename;
            $model->extension = 'mp4';
            if (!$model->save()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid validation while saving video');
            }
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Video successfully saved');
        }
        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Token is invalid');
    }
}
