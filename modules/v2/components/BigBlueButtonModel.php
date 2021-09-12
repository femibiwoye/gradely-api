<?php

namespace app\modules\v2\components;

use Yii;
use yii\base\Model;
use yii\helpers\Url;

class BigBlueButtonModel extends Model
{
    public $liveClassBaseUrl;
    public $bbbPath;
    public $allowStartStopRecording = true;
    public $autoStartRecording = false;
    public $attendeePW;
    public $meetingID;
    public $moderatorPW;
    public $name;
    public $record;
    public $voiceBridge = 74079;
    public $welcome = 'Welcome to gradely live class';
    public $logoutURL;
    public $maxParticipants;
    public $duration;
    public $userID;
    public $createTime;
    public $avatarURL;
    public $isBreakout; //Must provide parentMeetingID and sequence
    public $parentMeetingID;
    public $sequence;
    public $breakoutRoomsEnabled = 'true'; //If set to false, breakout rooms will be disabled
    public $breakoutRoomsPrivateChatEnabled = 'true'; //If set to false, the private chat will be disabled in breakout rooms.
    public $breakoutRoomsRecord = 'true'; //If set to false, breakout rooms will not be recorded.
    public $logo = 'https://gradely.ng/wp-content/themes/gradely/img/logo.svg';
    public $endWhenNoModeratorDelayInMinutes = 10;
    public $allowModsToUnmuteUsers = 'true';
    public $copyright = 'Gradely Inc.';
    public $bannerText = 'Gradely live class for learning';
    public $bannerColor = '#11BDCF';
    public $defaultWelcomeMessageFooter = 'Gradely live class for educational purposed';
    //public $userdataBbb_custom_style = ':root{--loader-bg:red;}.navbar--Z2lHYbG{background-color:pink!important;}';
    public $userdataBbb_custom_style = ':root{--loader-bg: #113255;}.overlay--1aTlbi{background-color: #113255 !important;}body{background-color: #113255 !important;}';
    public $userdataBbb_custom_style_url = 'https://gradly.s3.eu-west-2.amazonaws.com/assets/css/class-style.css';
//https://class.gradely.co/bigbluebutton/api/create?allowStartStopRecording=true
//&attendeePW=ap&autoStartRecording=false&duration=10&maxParticipants=2&meetingID=random-7870068&moderatorPW=mp
//&name=random-7870068&record=false&voiceBridge=79586&welcome=%3Cbr%3EWelcome+to+%3Cb%3E%25%25CONFNAME%25%25%3C%2Fb%3E%21&checksum=0af4cdda729008d795a98b5ca3261ba413f85b44
    private $checksum;

//https://class.gradely.co/bigbluebutton/api/join?avatarURL=https%3A%2F%2Fsummer.gradely.ng%2Fimg%2FfacilitatorOne.9f0182c7.png&fullName=User+1265046
//&meetingID=random-7870068&password=mp&redirect=true&userID=12345&checksum=f25bbb34f33cb632cfa0272f6d4382a38d7a1495
    //Join
    public $fullName = 'User';
    public $redirect = true;

    //Recording
    public $recordID;
    public $publish;

    public function init()
    {

        $this->bbbPath = '/bigbluebutton/api/';
        $this->checksum = Yii::$app->params['bbbSecret'];
        $this->liveClassBaseUrl = Yii::$app->params['bbbServerBaseUrl'];
    }

    public function CreateMeeting()
    {

//        $fullUrl = $this->liveClassBaseUrl . $this->bbbPath .
//            'create?' .
//            'allowStartStopRecording=' . $this->allowStartStopRecording .
//            '&attendeePW=' . $this->attendeePW .
//            '&autoStartRecording=' . $this->autoStartRecording .
//            '&meetingID=' . $this->meetingID .
//            '&moderatorPW=' . $this->moderatorPW .
//            '&name=' . $this->name .
//            '&record=' . $this->record . '&voiceBridge=' . $this->voiceBridge .
//            '&welcome=' . $this->welcome .
//            '&checksum=' . $this->checksum;

        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'create';
       $queryBuild = http_build_query([
            'logo' => $this->logo,
//            'allowStartStopRecording' => $this->allowStartStopRecording,
            'attendeePW' => $this->attendeePW,
//            'autoStartRecording' => $this->autoStartRecording,
            'meetingID' => $this->meetingID,
            'moderatorPW' => $this->moderatorPW,
            'name' => $this->name,
//            'record' => $this->record,
            'welcome' => $this->welcome . ' - ' . $this->name,
            'logoutURL' => Yii::$app->params['appBase'] . 'learning/live-class/end-class?status=1&token=' . $this->meetingID,
            'copyright' => $this->copyright,
//            'bannerText' => $this->bannerText,
//            'bannerColor' => $this->bannerColor,
            'defaultWelcomeMessageFooter' => $this->defaultWelcomeMessageFooter,
            'allowModsToUnmuteUsers' => $this->allowModsToUnmuteUsers,
            //'userdata-bbb_custom_style' => $this->userdataBbb_custom_style,
            'userdata-bbb_custom_style_url'=>$this->userdataBbb_custom_style_url,
            //'meta_endCallbackUrl' => Url::to(['/v2/learning/live-class/end-class-only','meetingID'=>$this->meetingID], true),
        ]);
        //$queryBuild = $queryBuild.'&userdata-bbb_custom_style=:root{--loader-bg:red;}.navbar--Z2lHYbG{background-color:pink!important;}';
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;


        return $this->ConvertXml($fullUrl);

    }

    public function JoinMeeting($moderator = false)
    {
//        $fullUrl = $this->liveClassBaseUrl . $this->bbbPath .
//            'join?fullName=' . $this->fullName .
//            '&meetingID=' . $this->meetingID .
//            '&password=' . $this->moderatorPW .
//            '&redirect=' . $this->redirect .
//            '&checksum=' . $this->checksum;
//
        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'join';

        $queryBuild = http_build_query([
            'avatarURL' => $this->avatarURL,
            'fullName' => $this->fullName,
            'meetingID' => $this->meetingID,
            'password' => $moderator ? $this->moderatorPW : $this->attendeePW,
            'redirect' => 'true',
            'userID' => $this->userID,
            //'userdata-bbb_custom_style_url'=>$this->userdataBbb_custom_style_url,
            'userdata-bbb_custom_style' => $this->userdataBbb_custom_style,
        ]);

        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        return $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;
    }

    public function MeetingStatus()
    {

        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'isMeetingRunning';
        $queryBuild = http_build_query([
            'meetingID' => $this->meetingID
        ]);
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;

        return $this->ConvertXml($fullUrl);
    }

    public function MeetingInfo()
    {
        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'getMeetingInfo';
        $queryBuild = http_build_query([
            'meetingID' => $this->meetingID,
            'password' => $this->moderatorPW,
        ]);
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;

        return $this->ConvertXml($fullUrl);
    }

    public function EndMeeting()
    {

        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'end';
        $queryBuild = http_build_query([
            'meetingID' => $this->meetingID,
            'password' => $this->moderatorPW,
        ]);
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;

        return $this->ConvertXml($fullUrl);
    }

    public function MeetingList()
    {

        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'getMeetings';
        $queryBuild = http_build_query([]);
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;

        return $this->ConvertXml($fullUrl);
    }

    public function GetMeetingConfig()
    {

        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'getDefaultConfigXML';
        $queryBuild = http_build_query([]);
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;

        return $this->ConvertXml($fullUrl);
    }

    public function GetPublishRecording()
    {
        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'publishRecordings';
        $queryBuild = http_build_query([
            'meetingID' => $this->meetingID,
            'recordID' => $this->recordID,
            'publish' => $this->publish
        ]);
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;

        return $this->ConvertXml($fullUrl);
    }

    public function GetRecordings()
    {
        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'deleteRecordings';
        $queryBuild = http_build_query([
            'recordID' => $this->recordID,
        ]);
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;

        return $this->ConvertXml($fullUrl);
    }

    public function UpdateRecordings()
    {
        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'updateRecordings';
        $queryBuild = http_build_query([
            'recordID' => $this->recordID,
        ]);
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;

        return $this->ConvertXml($fullUrl);
    }

    public function GetRecordingTextTracks()
    {
        $body = $this->liveClassBaseUrl . $this->bbbPath;
        $appName = 'getRecordingTextTracks';
        $queryBuild = http_build_query([
            'recordID' => $this->recordID,
        ]);
        $checkSum = sha1($appName . $queryBuild . $this->checksum);
        $fullUrl = $body . $appName . "?" . $queryBuild . "&checksum=" . $checkSum;

        return $this->ConvertXml($fullUrl);
    }

    private function ConvertXml($fullUrl)
    {
        $return = file_get_contents($fullUrl);
        $xml = simplexml_load_string($return);
        $model = json_encode($xml);
        return json_decode($model, TRUE);
    }

}
