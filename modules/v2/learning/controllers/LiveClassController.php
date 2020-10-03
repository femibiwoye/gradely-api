<?php

namespace app\modules\v2\learning\controllers;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\ClassAttendance;
use app\modules\v2\models\GenerateString;
use app\modules\v2\models\PracticeMaterial;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\TutorSession;
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

    public function actionStartClass()
    {

        $session_id = Yii::$app->request->post('session_id');
        $form = new \yii\base\DynamicModel(compact('session_id'));
        $form->addRule(['session_id'], 'required');

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $tutor_session = TutorSession::findOne(['id' => $session_id, 'requester_id' => Yii::$app->user->id]);

        if (!$tutor_session)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user');

        $teacher = TeacherClass::findOne(['teacher_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE]);

        if (Yii::$app->user->identity->type != 'teacher')
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user');

        $tutor_session->meeting_room = GenerateString::widget();
        $tutor_session->status = 'ongoing';

        //if($tutor_session->save()){

        $classAttendance = new ClassAttendance();
        $classAttendance->session_id = $tutor_session_id;
        $classAttendance->user_id = $teacher->teacher_id;
        $classAttendance->type = 'host';
        $classAttendance->save();
        //}

        $teacherName = $teacher->teacher->firstname . ' ' . $teacher->teacher->lastname;

        $payload = [
            "context" => [
                "user" => [
                    "avatar" => $teacher->teacher->image, //update
                    "name" => "{$teacherName}", //update
                    "email" => "{$teacher->teacher->email}" //update
                ]
            ],
            "aud" => Yii::$app->params['appName'],
            "iss" => Yii::$app->params['appName'],
            "sub" => "class.gradely.ng",
            "room" => "{$tutor_session->meeting_room}" //Update: tutor_session.meeting_room
        ];

        $token = UserJwt::encode($payload, Yii::$app->params['live_class_secret_token']);

        $tutor_session->meeting_token = $token;
        $tutor_session->token = $token;
        $tutor_session->save();

        return $token;
    }

    public function actionJoinClass()
    {

        $tutor_session_id = Yii::$app->request->post('id');
        $form = new \yii\base\DynamicModel(compact('tutor_session_id'));
        $form->addRule(['tutor_session_id'], 'required');

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $tutor_session = TutorSession::findOne($tutor_session_id);

        if (!$tutor_session)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user');

        $student = TutorSessionParticipant::findOne(['student_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE]);

        $schoolStudent = StudentSchool::findOne(['student_id' => Yii::$app->user->id]);


        if (Yii::$app->user->identity->type != 'student')
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user');


        if ($schoolStudent->class_id != $tutor_session->class_id)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Forbidden');


        if ($tutor_session->status == 'pending') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher has not started');
        } elseif ($tutor_session->status == 'completed') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class has finished!');
        } else {

            $classAttendance = new ClassAttendance();
            $classAttendance->session_id = $tutor_session_id;
            $classAttendance->user_id = Yii::$app->user->id;
            $classAttendance->type = 'attendee';

            $studentName = $student->student->firstname . ' ' . $student->student->lastname;

            $email = $student->student->email ? $student->student->email : 'support@gmail.com';
            $payload = [
                "context" => [
                    "user" => [
                        "avatar" => $student->student->image, //update
                        "name" => "{$studentName}", //update
                        "email" => "{$email}" //update
                    ]
                ],
                "aud" => Yii::$app->params['appName'],
                "iss" => Yii::$app->params['appName'],
                "sub" => "class.gradely.ng",
                "room" => "{$tutor_session->meeting_room}" //Update: tutor_session.meeting_room
            ];

            $token = UserJwt::encode($payload, Yii::$app->params['live_class_secret_token']);

            $classAttendance->token = $token;
            $classAttendance->save();

            return $token;
        }

    }

    public function actionEndClass($id)
    {

        $tutor_session = TutorSession::findOne($id);

        if (!$tutor_session)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid user');


        if ($tutor_session->requester_id == Yii::$app->user->id) {
            $tutor_session->status = 'completed';
            $tutor_session->save();
        }


        if ($tutor_session->status == 'ongoing' || $tutor_session->status == 'completed') {

            $attendance = ClassAttendance::find()->where(['user_id' => Yii::$app->user->id, 'session_id' => $id])->one();
            if ($attendance) {
                $attendance->ended_at = date('Y-m-d H:i:s');
                $attendance->save();
            }
        }

    }


    public function actionUpdateLiveClassVideo($token)
    {
        if (TutorSession::find()->where(['meeting_room' => $token])->exists() && !PracticeMaterial::find()->where(['filename'=>$token . '.mp4'])->exists()) {
            $tutorSession = TutorSession::find()->where(['meeting_room' => $token])->one();
            $model = new PracticeMaterial();
            $model->user_id = $tutorSession->requester_id;
            $model->type = SharedConstant::FEED_TYPE;
            $model->tag = 'live_class';
            $model->filetype = SharedConstant::TYPE_VIDEO;
            $model->title = $tutorSession->title;
            $model->filename = $token . '.mp4';
            $model->extension = 'mp4';
            if (!$model->save()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid validation while saving video');
            }
            return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Video successfully saved');
        }
        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Token is invalid');
    }
}
