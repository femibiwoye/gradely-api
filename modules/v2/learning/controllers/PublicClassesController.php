<?php

namespace app\modules\v2\learning\controllers;

use app\modules\v2\components\BigBlueButtonModel;
use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\models\campaign\CampaignLiveClass;
use Faker\Factory;

use Yii;
use yii\rest\Controller;
use yii\web\Response;

/**
 * Default controller for the `learning` module
 */
class PublicClassesController extends Controller
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
            'except' => ['get-class-link'],
        ];

        return $behaviors;
    }

    public function actionGetClassLink($class, $is_tutor = null, $name, $image = null, $user_id = null)
    {

        $tutor_session = CampaignLiveClass::findOne(['class_name' => $class]);
        if (empty($tutor_session)) {
            return ['url' => 'https://gradely.ng/tutoring', 'title' => 'Class does not exist. Go back home.'];
        }

        $isTutor = false;
        if ($is_tutor == 1) {
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
        $bbbModel->classSource = 'public';
        $create = null;
        if ($bbbModel->MeetingStatus()['running'] == 'false') {
            $create = $bbbModel->CreateMeeting();
        }

//        if ($create) {
            if ($isTutor) {
                $bbbModel->fullName = !empty($name) ? $name : $tutor_session->tutor_name;
//                $bbbModel->moderatorPW = 'moderatorPW';
                $bbbModel->avatarURL = !empty($image) ? $image : $tutor_session->tutor_image;
                $bbbModel->userID = !empty($user_id) ? $user_id : $tutor_session->tutor_email;
            } else {
                $bbbModel->fullName = $name;
                if (!empty($image))
                    $bbbModel->avatarURL = $image;
                if (!empty($user_id))
                    $bbbModel->userID = $user_id;
//                $bbbModel->attendeePW = 'attendeePW';
            }

//            $destinationLink = $bbbModel->JoinMeeting($isTutor);
//        } else {
//            $destinationLink = null;
//        }

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $bbbModel->JoinMeeting($isTutor);

//        return $this->redirect("$destinationLink");
//        $model = ['url' => $destinationLink, 'title' => 'Join Class now.'];
//        return $this->render('index', ['model' => $model]);
    }

}
