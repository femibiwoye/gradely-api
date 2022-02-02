<?php

namespace app\modules\v2\learning\controllers;

use app\modules\v2\components\BigBlueButtonModel;
use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\LogError;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\campaign\CampaignLiveClass;
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
use Faker\Generator;
use Faker\Provider\Address;
use SebastianBergmann\CodeCoverage\Util;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Controller;
use app\modules\v2\models\TutorSessionParticipant;

use app\modules\v2\components\UserJwt;

use Yii;
use yii\web\Response;

/**
 * Default controller for the `learning` module
 */
class PublicClassController extends Controller
{


//    public function behaviors()
//    {
//        $behaviors = parent::behaviors();
//        //For CORS
//        $auth = $behaviors['authenticator'];
//        unset($behaviors['authenticator']);
//
//        $behaviors['corsFilter'] = [
//            'class' => \yii\filters\Cors::className(),
//        ];
//
//        //$behaviors['authenticator'] = $auth;
//        $behaviors['authenticator'] = [
//            'class' => CustomHttpBearerAuth::className(),
//            'except' => ['start-class','create-class'],
//        ];
//
//        return $behaviors;
//    }

    public function beforeAction($action)
    {

        \Yii::$app->response->format = Response::FORMAT_HTML;
        $this->layout = '@app/views/layouts/empty';
        return parent::beforeAction($action);
    }


    /**
     * The host starts the meeting.
     * @return ApiResponse
     * @throws \Exception
     */
    public function actionCreateClass($class, $name, $image = null, $access, $email)
    {
//        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;

        if ($access != 'Gr4d3lly$') {
            $model = ['url' => 'https://gradely.ng/tutoring', 'title' => 'Invalid access, go back home'];
            return $this->render('index', ['model' => $model]);
        }

        if (CampaignLiveClass::find()->where(['class_name' => $class])->exists()) {
            $model = ['url' => 'https://gradely.ng/tutoring', 'title' => 'Class name already taken, go back home'];
            return $this->render('index', ['model' => $model]);
        }


        $tutor_session = new CampaignLiveClass();
        $tutor_session->tutor_name = $name;
        $tutor_session->tutor_email = $email;
        $tutor_session->tutor_image = $image;
        $tutor_session->class_name = $class;
        $tutor_session->tutor_access = 'Gr4d3lly$';
        if (!$tutor_session->save()) {
            $model = ['url' => 'https://gradely.ng/tutoring', 'title' => 'Something went wrong while creating'];
            return $this->render('index', ['model' => $model]);
        }

        $model = ['url' => 'https://gradely.ng/tutoring', 'title' => 'Successful, go ahead and start class.'];
        return $this->render('index', ['model' => $model]);
    }


    /**
     * The host starts the meeting.
     * @return ApiResponse
     * @throws \Exception
     */
    public function actionStartClass($class, $access = null)
    {

//        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        $tutor_session = CampaignLiveClass::findOne(['class_name' => $class]);
        if (empty($tutor_session)) {
            $model = ['url' => 'https://gradely.ng/tutoring', 'title' => 'Class does not exist. Go back home.'];
            return $this->render('index', ['model' => $model]);
        }

        $isTutor = false;
        if ($tutor_session->tutor_access == $access) {
            $isTutor = true;
        }

        if (!CampaignLiveClass::find()->where(['class_name' => $class, 'status' => ['pending', 'ongoing']])->exists()) {
            $tutor_session = new CampaignLiveClass();
            $tutor_session->attributes = CampaignLiveClass::findOne(['class_name' => $class])->attributes;

            $tutor_session->save();
        }

        $bbbModel = new BigBlueButtonModel();
        $bbbModel->meetingID = $tutor_session->class_name;
        $bbbModel->moderatorPW = 'moderatorPW';
        $bbbModel->attendeePW = 'attendeePW';

        $bbbModel->name = $tutor_session->class_name;
        $create = null;
        if ($bbbModel->MeetingStatus()['running'] == 'false') {
            $create = $bbbModel->CreateMeeting();
        }
        if ($create) {
            if ($isTutor) {
                $bbbModel->fullName = $tutor_session->tutor_name;
                $bbbModel->avatarURL = $tutor_session->tutor_image;
                $bbbModel->userID = $tutor_session->tutor_email;
            } else {
                $faker = Factory::create();
                $bbbModel->fullName = $faker->name;
            }

//            $destinationLink = $bbbModel->JoinMeeting($isTutor);
        } else {
            $destinationLink = null;
        }
        $destinationLink = $bbbModel->JoinMeeting($isTutor);

        $model = ['url' => $destinationLink, 'title' => 'Join Class now.'];
        return $this->render('index', ['model' => $model]);
    }

}
