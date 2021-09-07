<?php

namespace app\modules\v2\learning\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use Yii;
use BigBlueButton\BigBlueButton;

/**
 * BigBlueButton controller for the `learning` module
 */
class BigBlueButtonController extends Controller
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
        ];

        return $behaviors;
    }

    public function actionIndex()
    {
        $bbb                 = new BigBlueButton();
        $createMeetingParams = new CreateMeetingParameters('bbb-meeting-uid-65', 'BigBlueButton API Meeting');
        $response            = $bbb->createMeeting($createMeetingParams);

        echo "Created Meeting with ID: " . $response->getMeetingId();
    }


}
