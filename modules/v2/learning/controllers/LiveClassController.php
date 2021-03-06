<?php

namespace app\modules\v2\learning\controllers;

use app\modules\v2\components\BigBlueButtonModel;
use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\LogError;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\ClassAttendance;
use app\modules\v2\models\Classes;
use app\modules\v2\models\GenerateString;
use app\modules\v2\models\handler\SessionLogger;
use app\modules\v2\models\Parents;
use app\modules\v2\models\PracticeMaterial;
use app\modules\v2\models\Questions;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\TutorSession;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Faker\Factory;
use SebastianBergmann\CodeCoverage\Util;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
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
            'class' => CustomHttpBearerAuth::className(),
            'except' => ['update-live-class-video', 'end-class-only', 'log-recording', 'public-class','visitor-join'],
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }


        $tutor_session = TutorSession::findOne(['id' => $session_id, 'requester_id' => $requester_id]);
        if (empty($tutor_session->meeting_room)) {
            $tutor_session->meeting_room = GenerateString::widget();
            $tutor_session->save();
        }


        if (Yii::$app->params['liveClassClient'] == 'bbb') {
            if(empty($tutor_session->extra_meta)) {
                $moderatorPW = GenerateString::widget(['length' => 20]);
                $attendeePW = GenerateString::widget(['length' => 20]);

                $tutor_session->extra_meta = ['moderatorPW' => $moderatorPW, 'attendeePW' => $attendeePW];
            }

            $user = Yii::$app->user->identity;
            $bbbModel = new BigBlueButtonModel();
            $bbbModel->meetingID = $tutor_session->meeting_room;
            $bbbModel->moderatorPW = $tutor_session->extra_meta['moderatorPW'];
            $bbbModel->attendeePW = $tutor_session->extra_meta['attendeePW'];


            $bbbModel->name = $tutor_session->title;
            $bbbModel->class_id = $tutor_session->class;
            $bbbModel->tutor_session_id = $tutor_session->id;
            $bbbModel->classSource = $tutor_session->category;
            $create = $bbbModel->CreateMeeting();
            if ($create) {
                $bbbModel->fullName = $user->firstname . ' ' . $user->lastname;
                $bbbModel->avatarURL = $user->image;
                $bbbModel->userID = $user->email;
                $bbbModel->moderatorPW = $tutor_session->extra_meta['moderatorPW'];
                $destinationLink = $bbbModel->JoinMeeting(true);
                $token = $tutor_session->meeting_token ?? $create['internalMeetingID'] ?? $tutor_session->meeting_token = $bbbModel->MeetingInfo()['internalMeetingID'];
            } else {
                $destinationLink = null;
            }


            //$destinationLink = $this->classUrl($tutor_session, $token);
        } else {
            $payload = $this->getPayload(Yii::$app->user->identity, $tutor_session->meeting_room);
            $token = UserJwt::encode($payload, Yii::$app->params['live_class_secret_token']);
            $destinationLink = $this->classUrl($tutor_session, $token);
        }
        $this->classAttendance($session_id, $requester_id, SharedConstant::LIVE_CLASS_USER_TYPE[0], $token);

        if ($saveStatus = TutorSession::updateAll(['status' => 'ongoing', 'meeting_token' => $token,'extra_meta'=>$tutor_session->extra_meta], ['meeting_room' => $tutor_session->meeting_room])) {
            $tutor_session->status = 'ongoing';
            $tutor_session->meeting_token = $token;
            $saveStatus = $tutor_session->save();
            if (!$saveStatus)
                return (new ApiResponse)->error($tutor_session->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save your session');
        }


        return (new ApiResponse)->success($destinationLink, ApiResponse::SUCCESSFUL, $tutor_session->status);
    }

    private function classUrl(TutorSession $session, $token)
    {
        return Yii::$app->params['live_class_url'] . $session->meeting_room . '?jwt=' . $token . '#config.subject=%22' . $session->title . '%22';
    }

    /**
     * Participant or student joining a class
     *
     * @return ApiResponse
     */
    public function actionJoinClass($child = null)
    {

        $session_id = Yii::$app->request->post('session_id');
        $user_id = Yii::$app->user->id;
        $type = Yii::$app->user->identity->type;
        if (empty($child)) {
            $child = Yii::$app->request->post('child_id');
        }
        $form = new \yii\base\DynamicModel(compact('session_id'));
        $form->addRule(['session_id'], 'required')
            ->addRule(['session_id'], 'exist', ['targetClass' => TutorSession::className(), 'targetAttribute' => ['session_id' => 'id']]);
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        // TODO Allow school and parent to be able to join
//        if (Yii::$app->user->identity->type != 'student')
//            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user');

        $student = TutorSessionParticipant::find()->where(['session_id' => $session_id, 'participant_id' => $user_id])->exists();

        $tutor_session = TutorSession::findOne(['id' => $session_id]);
        $schoolStudent = $type == 'student' && StudentSchool::find()->where(['student_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE, 'class_id' => $tutor_session->class])->exists() ? true : false;
        $teacherID = $type == 'teacher' && TeacherClass::find()->where(['teacher_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE, 'class_id' => $tutor_session->class])->exists() ? true : false;
        $parentStatus = $type == 'parent' && Parents::find()->where(['parent_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE, 'student_id' => $child])->exists() ? true : false;
        $schoolStatus = $type == 'school' && Classes::find()->where(['school_id' => Utility::getSchoolAccess(), 'id' => $tutor_session->class])->exists() ? true : false;

        if (!$student && !$schoolStudent && !$teacherID && !$parentStatus && !$schoolStatus)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You are either not in class or not a participant');


        $bbbModel = new BigBlueButtonModel();
        $bbbModel->meetingID = $tutor_session->meeting_room;
        if ($tutor_session->status == 'pending') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher has not started the class');
        } elseif ($tutor_session->status == 'completed' || $bbbModel->MeetingStatus()['running'] == 'false') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, $tutor_session->status);
        } elseif ($tutor_session->status == 'ongoing' && $bbbModel->MeetingStatus()['running'] == 'true') {
            if (Yii::$app->params['liveClassClient'] == 'bbb') {
                $user = Yii::$app->user->identity;
                $bbbModel->fullName = $user->firstname . ' ' . $user->lastname;
                $bbbModel->avatarURL = $user->image;
                $bbbModel->userID = $user->email;
                $bbbModel->classSource = $tutor_session->category;
                if ($user->id == $tutor_session->requester_id) {
                    $bbbModel->moderatorPW = $tutor_session->extra_meta['moderatorPW'];
                    $destinationLink = $bbbModel->JoinMeeting(true, $child);
                } else {
                    $bbbModel->attendeePW = $tutor_session->extra_meta['attendeePW'];
                    $destinationLink = $bbbModel->JoinMeeting(false, $child);
                }
                $token = $tutor_session->meeting_token;
            } else {
                $payload = $this->getPayload(Yii::$app->user->identity, $tutor_session->meeting_room);
                $token = UserJwt::encode($payload, Yii::$app->params['live_class_secret_token']);
                $destinationLink = $this->classUrl($tutor_session, $token);
            }
            $this->classAttendance($session_id, $user_id, SharedConstant::LIVE_CLASS_USER_TYPE[1], $token);
            return (new ApiResponse)->success($destinationLink, ApiResponse::SUCCESSFUL, $tutor_session->status);
        } else {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid class status');
        }

    }

    public function actionCheckClassStatus($meeting_room)
    {
        $bbbModel = new BigBlueButtonModel();
        $bbbModel->meetingID = $meeting_room;
        return (new ApiResponse)->error($bbbModel->MeetingStatus(), ApiResponse::SUCCESSFUL);
    }

    /**
     * Ended class
     * @param $id
     * @return ApiResponse
     */
    public function actionEndClass($token = null)
    {
        $user_id = Yii::$app->user->id;
        $is_owner = false;
        $is_completed = false;

        if (!empty($token)) {
//            $form = new \yii\base\DynamicModel(compact('session_id', 'user_id'));
//            $form->addRule(['session_id'], 'required')
//                ->addRule(['session_id'], 'exist', ['targetClass' => ClassAttendance::className(), 'targetAttribute' => ['session_id', 'user_id']]);
//            if (!$form->validate()) {
//                return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
//            }
            $tutor_session = TutorSession::findOne(['meeting_room' => $token]);

            if (!$tutor_session)
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid session');
            $attendance = ClassAttendance::find()->where(['user_id' => $user_id, 'session_id' => $tutor_session->id])->one();
            if (!empty($attendance->ended_at))
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Meeting already ended');


            $bbbModel = new BigBlueButtonModel();
            $bbbModel->meetingID = $tutor_session->meeting_room;

            if ($tutor_session->requester_id == $user_id && $bbbModel->MeetingStatus()['running'] == 'false') {
                if (!TutorSession::updateAll(['status' => 'completed'], ['meeting_room' => $tutor_session->meeting_room])) {
                    $tutor_session->status = 'completed';
                    if ($tutor_session->save()) {
                        $bbbModel->moderatorPW = $tutor_session->extra_meta['moderatorPW'];
                        $bbbModel->EndMeeting();
                    }
                }
                $is_owner = true;
            }
            if ($attendance) {
                $attendance->ended_at = date('Y-m-d H:i:s');
                $attendance->save();
                $is_completed = true;
            }

        } else {
            if (TutorSession::find()->where(['requester_id' => $user_id, 'status' => 'ongoing'])->exists()) {
                TutorSession::updateAll(['status' => 'completed', 'session_ended' => date('Y-m-d H:i:s')], ['requester_id' => $user_id, 'status' => 'ongoing']);
                $is_owner = true;
                $is_completed = true;
            }

            if (ClassAttendance::find()->where(['AND', ['user_id' => $user_id], ['not', ['ended_at' => null]]])->exists()) {
                ClassAttendance::updateAll(['ended_at' => date('Y-m-d H:i:s')], ['AND', ['user_id' => $user_id], ['not', ['ended_at' => null]]]);
                $is_completed = true;
            }
        }


        return (new ApiResponse)->success(['status' => $is_completed, 'is_owner' => $is_owner], ApiResponse::SUCCESSFUL, 'Class ended');
    }


    /**
     * @param $meetingID
     * @return ApiResponse|bool
     */
    public function actionEndClassOnly($meetingID)
    {

        if (!empty($meetingID)) {
            $form = new \yii\base\DynamicModel(compact('meetingID'));
            $form->addRule(['meetingID'], 'required')
                ->addRule(['meetingID'], 'exist', ['targetClass' => TutorSession::className(), 'targetAttribute' => ['meetingID' => 'meeting_room']]);
            if (!$form->validate()) {
                return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
            }
            if ($tutor_session = TutorSession::findOne(['meeting_room' => $meetingID, 'status' => 'ongoing'])) {
                $tutor_session->status = 'completed';
                $tutor_session->session_ended = date('Y-m-d H:i:s');
                if ($tutor_session->save()) {
                    if (ClassAttendance::find()->where(['AND', ['session_id' => $tutor_session->id], ['not', ['ended_at' => null]]])->exists()) {
                        ClassAttendance::updateAll(['ended_at' => date('Y-m-d H:i:s')], ['AND', ['session_id' => $tutor_session->id], ['not', ['ended_at' => null]]]);
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function actionLogRecording()
    {
        $post = Yii::$app->request->post('signed_parameters');
        try {
            if (!$modelJWT = UserJwt::decode($post, Yii::$app->params['bbbSecret'], ['HS256'])) {
                return null;
            }
            $meetingID = $modelJWT->meeting_id;
            $recordID = $modelJWT->record_id;
            $model = new BigBlueButtonModel();
            $model->meetingID = $meetingID;
            $model->recordID = $recordID;
            $data = $model->GetRecordings(true);
            if (!isset($data['recordings'])) {
                return null;
            }

            if (!$tutorSession = TutorSession::find()->where(['meeting_room' => $meetingID, 'status' => 'completed'])->one()) {
                return null;
            }

            if (!empty($tutorSession->recording)) {
                return 'recorded';
            }
            //$tutorSession = TutorSession::findOne(['meeting_room' => $meetingID]);
            $tutorSession->recording = $data['recordings'] ?? null;
            if ($tutorSession->save() && !empty($tutorSession->recording)) {
                $playback = $data['recordings']['recording']['playback']['format'];
                if (!PracticeMaterial::find()->where(['filename' => $playback['url']])->exists()) {
                    $tutorSession = TutorSession::find()->where(['meeting_room' => $meetingID])->one();
                    $model = new PracticeMaterial(['scenario' => 'live-class-material']);
                    $model->user_id = $tutorSession->requester_id;
                    $model->type = SharedConstant::FEED_TYPE;
                    $model->tag = 'live_class';
                    $model->filetype = SharedConstant::TYPE_VIDEO;
                    $model->title = $tutorSession->title;
                    $model->filename = $playback['url'];
                    $model->extension = 'mp4';
                    $model->filesize = Utility::FormatBytesSize($playback['size']);
                    $model->thumbnail = $playback['preview']['images']['image'][0];
                    if (!$model->save()) {
                        return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid validation while saving video');
                    }
                    $model->saveFileFeed($tutorSession->class);
                    return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Video successfully saved');
                }
            }

        } catch (\Exception $e) {
            \Sentry\captureException($e);
            return $e;
        }

    }


    /**
     * This receives the video when meeting ends
     *
     * This function is dynamic, the video from live class is first processed to live db, if the meeting token does not exist, it checks test database.
     * It then save the recorded video to the database that has the meeting token.
     *
     * @param $filename
     * @return ApiResponse
     * @throws \Exception
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

        $fileUrl = Yii::$app->params['live_class_recorded_url'] . "$meeting_room/$filename";

        $status = false;
        $db = Yii::$app->db;
        $isTest = false;
        if (TutorSession::find()->where(['meeting_room' => $meeting_room])->exists(Yii::$app->db)) {
            $status = true;
            $dbName = SharedConstant::DB_CONNECTION_NAME[0];
            $db = Yii::$app->$dbName;
            $isTest = false;
        } elseif (TutorSession::find()->where(['meeting_room' => $meeting_room])->exists(Yii::$app->db_test)) {
            $status = true;
            $dbName = SharedConstant::DB_CONNECTION_NAME[1];
            $db = Yii::$app->$dbName;
            $isTest = true;
        }

        if ($status && !PracticeMaterial::find()->where(['filename' => $filename])->exists($db)) {
            $tutorSession = TutorSession::find()->where(['meeting_room' => $meeting_room])->one($db);
            PracticeMaterial::$database = $dbName;
            $model = new PracticeMaterial(['scenario' => 'live-class-material']);
            $model->user_id = $tutorSession->requester_id;
            $model->type = SharedConstant::FEED_TYPE;
            $model->tag = 'live_class';
            $model->filetype = SharedConstant::TYPE_VIDEO;
            $model->title = $tutorSession->title;
            $model->filename = $fileUrl;
            $model->extension = 'mp4';
            //fileSize actionFileDetail is throwing error due to AWS GetObject class
            try {
                $model->filesize = isset($this->actionFileDetail($fileUrl)['fileSize']) ? $this->actionFileDetail($fileUrl)['fileSize'] : "0";
            } catch (\Exception $exception) {
                \Sentry\captureException($exception);
                \app\modules\v2\components\LogError::widget(['source' => 'API', 'name' => 'Live class error', 'raw' => $exception]);
            }

            if (!$model->save()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid validation while saving video');
            }
            $model->saveFileFeed($tutorSession->class, $isTest);
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Video successfully saved');
        }
        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Token is invalid');
    }


    public function actionReschedule($id)
    {
        $availability = Yii::$app->request->post('availability');
        if (empty($availability)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Availability time cannot be blank!');
        }

        $model = TutorSession::find()
            ->where(['id' => $id, 'requester_id' => Yii::$app->user->id, 'status' => 'pending'])
            ->one();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model->availability = $availability;
        $model->scenario = 'update-class';
        if (!$model->save()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR, 'Record not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record updated');
    }

    public function actionDelete($id)
    {
        $model = TutorSession::findOne(['id' => $id, 'requester_id' => Yii::$app->user->id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        if (!$model->delete()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR, 'Record not deleted');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Record deleted successfully!');
    }

    public function actionUpdate($id)
    {
        $subject_id = Yii::$app->request->post('subject_id');
        $title = Yii::$app->request->post('title');
        $validate = new \yii\base\DynamicModel(compact('subject_id', 'title'));
        $validate->addRule(['subject_id', 'title'], 'required');
        if (!$validate->validate()) {
            return (new ApiResponse)->error($validate->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $model = TutorSession::find()
            ->where(['id' => $id, 'requester_id' => Yii::$app->user->id])
            ->one();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model->subject_id = $subject_id;
        $model->title = $title;
        if (!$model->save()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR, 'Record not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record updated successfully');
    }

    public function actionFileDetail($url)
    {
        $key = explode("/", $url, 5);
        $name = $key[4]; //This get the folder/filename.ext

        $credentials = new Credentials(Yii::$app->params['AwsS3Key'], Yii::$app->params['AwsS3Secret']);
        $config = [
            'version' => 'latest',
            'region' => 'eu-west-2',
            'credentials' => $credentials
        ];
        $s3Client = new S3Client($config);
        $result = $s3Client->getObject([
            'Bucket' => 'recordings.gradely.ng',
            'Key' => $name,
            //'SaveAs' => $name
        ]);
        $fileSize = Utility::FormatBytesSize($result['ContentLength']);
        $response = array_merge(ArrayHelper::toArray($result), ['fileSize' => $fileSize]);
        return $response;
    }

    public function actionPublicClass($tutor_name, $user_name)
    {

        $bbbModel = new BigBlueButtonModel();
        $bbbModel->meetingID = GenerateString::widget();
        $bbbModel->moderatorPW ='moderatorPW';
        $bbbModel->attendeePW = 'attendeePW';
        $bbbModel->record = 'false';
        $bbbModel->allowStartStopRecording = 'false';
        $bbbModel->welcome = 'Welcome to Gradely Counselling session';


        $bbbModel->name = 'Counselling Session';
//        $bbbModel->class_id = $tutor_session->class;
//        $bbbModel->tutor_session_id = $tutor_session->id;

        $create = $bbbModel->CreateMeeting();
        if ($create) {
            $bbbModel->fullName = $tutor_name;
            $bbbModel->moderatorPW = 'moderatorPW';
            $destinationLinkTutor = $bbbModel->JoinMeeting(true);
            $bbbModel->fullName = $user_name;
            $bbbModel->moderatorPW = 'attendeePW';
            $destinationLinkUser = $bbbModel->JoinMeeting(true);
        } else {
            $destinationLink = null;
        }


        return (new ApiResponse)->success(["tutor_link"=>$destinationLinkTutor,"user_link"=>$destinationLinkUser], ApiResponse::SUCCESSFUL);


    }

    public function actionVisitorJoin($meeting_room)
    {
        if (!TutorSession::find()->where(['meeting_room' => $meeting_room, 'status' => ['pending', 'ongoing']])->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Class has ended or does not exists');
        }

        $tutorSession = TutorSession::find()->where(['meeting_room' => $meeting_room, 'status' => ['pending', 'ongoing']])->one();

        $bbbModel = new BigBlueButtonModel();
        $bbbModel->meetingID = $meeting_room;

        if(empty($tutorSession->extra_meta)) {
            $moderatorPW = GenerateString::widget(['length' => 20]);
            $attendeePW = GenerateString::widget(['length' => 20]);

            $tutorSession->extra_meta = ['moderatorPW' => $moderatorPW, 'attendeePW' => $attendeePW];
            $tutorSession->save();
        }
        $bbbModel->name = $tutorSession->title;
        $create = null;
        if ($bbbModel->MeetingStatus()['running'] == 'false') {
            $bbbModel->moderatorPW = $tutorSession->extra_meta['moderatorPW'];
            $bbbModel->attendeePW = $tutorSession->extra_meta['attendeePW'];
            $create = $bbbModel->CreateMeeting();
        }
        if ($create) {
                $faker = Factory::create();
                $bbbModel->fullName = $faker->name;

        }
        $bbbModel->attendeePW = $tutorSession->extra_meta['attendeePW'];
        $destinationLink = $bbbModel->JoinMeeting(false);
        return (new ApiResponse)->success($destinationLink, ApiResponse::SUCCESSFUL);
    }
}
