<?php

namespace app\modules\v2\controllers;


use app\modules\v2\components\BigBlueButtonModel;
use app\modules\v2\components\Pricing;
use app\modules\v2\components\Recommendation;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\FileLog;
use app\modules\v2\models\games\Games;
use app\modules\v2\models\games\Group;
use app\modules\v2\models\games\Level;
use app\modules\v2\models\games\Subject;
use app\modules\v2\models\GenerateString;
use app\modules\v2\models\PracticeMaterial;
use app\modules\v2\models\QuizSummaryDetails;
use app\modules\v2\models\Recommendations;
use app\modules\v2\models\SchoolCalendar;
use app\modules\v2\models\Schools;
use app\modules\v2\models\TutorSession;
use app\modules\v2\models\User;
use app\modules\v2\models\VideoContent;
use app\modules\v2\school\models\ClassForm;
use Aws\S3\S3Client;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Media\Video;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\rest\Controller;


/**
 * Auth controller
 */
class CommandsController extends Controller
{

    /**
     * Update school calendar
     * @return int
     */
    public function actionUpdateSchoolCalendar()
    {
        return SchoolCalendar::updateAll([
            'first_term_start' => Yii::$app->params['first_term_start'],
            'first_term_end' => Yii::$app->params['first_term_end'],
            'second_term_start' => Yii::$app->params['second_term_start'],
            'second_term_end' => Yii::$app->params['second_term_end'],
            'third_term_start' => Yii::$app->params['third_term_start'],
            'third_term_end' => Yii::$app->params['third_term_end'],
            'year' => date('Y'),
            'session_name' => date('Y') + 1
        ], ['status' => 1]);
    }

    /**
     * For videos that does not have token generated. It will generate unique token to the content.
     * @return bool
     * @throws \Exception
     */
    public function actionUpdateVideoToken()
    {
        $videos = VideoContent::find()->where(['token' => null])->all();

        foreach ($videos as $video) {
            $token = GenerateString::widget(['length' => 20]);
            if (VideoContent::find()->where(['token' => $token])->exists()) {
                $video->token = GenerateString::widget(['length' => 20]);
            }
            $video->token = $token;
            $video->save();
        }

        return true;
    }

    /**
     * For media files that does not have token generated. It will generate unique token to the files.
     * @return bool
     * @throws \Exception
     */
    public function actionUpdateFileToken()
    {
        $files = PracticeMaterial::find()->where(['token' => null])->all();

        foreach ($files as $file) {
            $token = GenerateString::widget(['length' => 50]);
            if (PracticeMaterial::find()->where(['token' => $token])->exists()) {
                $file->token = GenerateString::widget(['length' => 50]);
            }
            $file->token = $token;
            $file->save();
        }

        return true;
    }


    /**
     * Generate daily recommendation
     * @return ApiResponse
     */
    public function actionGenerateDailyRecommendations()
    {
        //student_recommendations depicts the students that has received the daily recommendation
        $student_recommendations = ArrayHelper::getColumn(
            Recommendations::find()
                ->where([
                    'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ONE],
                    'DATE(created_at)' => date('Y-m-d')
                ])
                ->andWhere('DAY(CURDATE()) = DAY(created_at)')//checking on-going day
                ->all(),
            'student_id'
        );

        //student_ids depicts the list of students
        $student_ids = ArrayHelper::getColumn(
            User::find()->where(['type' => SharedConstant::TYPE_STUDENT])->andWhere(['<>', 'status', SharedConstant::VALUE_ZERO])->andWhere(['NOT IN', 'id', $student_recommendations])->all(),
            'id'
        );


        if (empty($student_ids)) {
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Daily recommendations are already generated');
        }

        $key = 0;
        foreach ($student_ids as $student) {
            try {
                $recommendation = new Recommendation();
                if ($recommendation->dailyRecommendation($student))
                    $key++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, ($key) . ' students generated');
    }


    public function actionVideoThumbnailExtractor()
    {

        $path = Url::to('@webfolder/thumbnails/videos/');
        Utility::DeleteFolderWithFiles($path);
        if (!Utility::CreateFolder($path))
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'There is error with the folder');

        $models = PracticeMaterial::find()->where(['filetype' => 'video', 'thumbnail' => null])->all();
        foreach ($models as $model) {
            try {

                //$file = 'https://s3.eu-west-2.amazonaws.com/recordings.gradely.ng/recordings/td6z7ljkhhwdkfwuu54jyt2zs36kd7/td6z7ljkhhwdkfwuu54jyt2zs36kd7_2020-11-11-20-11-46.mp4';
                $file = $model->filename;
                if (empty(pathinfo($file, PATHINFO_EXTENSION)) || !filter_var($file, FILTER_VALIDATE_URL)) {
                    continue;
                }
                $fileName = pathinfo($file, PATHINFO_FILENAME);

                $imageName = "$fileName.jpg";
                $ffmpeg = FFMpeg::create([
                        'ffmpeg.binaries' => exec('which ffmpeg'),
                        'ffprobe.binaries' => exec('which ffprobe')
                    ]
                );
                $video = $ffmpeg->open($file);
                $frame = $video->frame(TimeCode::fromSeconds(5))
                    ->addFilter(new \FFMpeg\Filters\Frame\CustomFrameFilter('scale=500x300'));
                $frame->save($path . "$imageName");

                $key = 'files/thumbnails/' . $imageName;
                $s3Client = new S3Client(Utility::AwsS3Config());
                $awsResponnse = $result = $s3Client->putObject([
                    'Bucket' => Yii::$app->params['AwsS3BucketName'],
                    'Key' => $key,
                    'SourceFile' => $path . "$imageName",
                ]);
                if (isset($awsResponnse['ObjectURL'])) {
                    $model->thumbnail = $awsResponnse['ObjectURL'];
                    $model->save();
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }

    public function actionPopulateSchoolClasses()
    {
        foreach (Schools::find()->leftJoin('classes', 'classes.school_id = schools.id')->where(['classes.id' => null])->all() as $school) {
            if (!Classes::find()->where(['school_id' => $school->id])->exists()) {
                $form = new ClassForm();
                $form->school_format = $school->naming_format;
                $form->school_type = $school->school_type;
                if (!$form->generateClasses($school)) {
                    continue;
                }
            }
        }
    }

    public function actionSchoolSubscribeStudents()
    {
        foreach (Schools::find()->innerJoin('student_school ss', 'ss.school_id = schools.id')->where(['ss.status' => 1, 'ss.is_active_class' => 1])->all() as $school) {
            Pricing::SchoolStudentFirstTimeSubscription($school);
        }
    }

//    public function actionTestSubscription()
//    {
//        return Pricing::SchoolAddStudentSubscribe([207]);
//    }


    public function actionGames()
    {
        $url = 'https://partners.9ijakids.com/index.php?partnerId=247807&accessToken=5f63d1c5-3f00-4fa5-b096-9ffd&action=catalog';
        $curl = curl_init("$url");

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.64 Safari/537.31'
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            //'Authorization: Bearer ' . Yii::$app->params['wizItUpKey'],
            'Content-Type: application/json',
            "Cache-Control: no-cache",
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $models = json_decode($response);

//        foreach ($models as $item) {
//            if (Subject::find()->where(['name' => $item->Subject])->exists() || empty($item->Subject)) {
//                continue;
//            }
//
//            $model = new Subject();
//            $model->name = $item->Subject;
//            $model->description = $item->Description;
//            $model->save();
//        }

        foreach ($models as $item) {
            if (Games::find()->where(['game_title' => $item->GameTitle])->exists()) {
                continue;
            }

            $model = new Games();
            $model->category_name = $item->CategoryName;
            $model->group = $item->Group;
            $model->level = $item->Level;
            $model->subject = $item->Subject;
            $model->topic = $item->Topic;
            $model->game_id = $item->GameID;
            $model->topic = $item->Topic;
            $model->game_title = $item->GameTitle;
            $model->description = $item->GameDescription;
            $model->image = $item->GameImage;
            $model->provider = '9ijakids.com';
            $token = GenerateString::widget(['length' => 30]);
            if (Games::find()->where(['token' => $token])->exists()) {
                $model->token = GenerateString::widget(['length' => 30]);
            }
            $model->token = $token;
            $model->save();
        }
    }


    public function actionWizitupContent($month = null, $year = null, $type = 'file')
    {

        if (empty($month) || empty($year)) {
            $month = date('m');
            $year = date('Y');
        }

        if ($type == 'user') {
            $model = FileLog::find()
                ->select([
                    new Expression('count(*) as videos_count'),
                    //'file_id',
                    //'file_log.class_id',
                    'file_log.user_id',
                    'vc.content_id',
                    'user.subscription_plan',
                    'user.subscription_expiry',
                    'user.firstname',
                    'user.lastname',
                    'user.code',
                    'parent.email',
                    'schools.name',
                    'schools.subscription_expiry school_sub_expiry',
                    'schools.subscription_plan school_sub_plan',
                    'sc.subscription_status'
                ])
                ->leftJoin('video_content vc', 'vc.id = file_log.file_id')
                ->leftJoin('user', 'user.id = file_log.user_id')
                ->leftJoin('student_school sc', 'sc.student_id = user.id AND sc.status = 1 AND sc.current_class = 1 AND sc.current_class = 1')
                ->leftJoin('schools', 'schools.id = sc.school_id')
                ->leftJoin('parents', 'parents.student_id = user.id')
                ->leftJoin('user parent', 'parent.id = parents.parent_id')
                ->groupBy(['file_log.user_id'])
                ->where(['source' => 'catchup', 'user.type' => 'student'])
                ->andWhere("YEAR(date(file_log.created_at)) = $year AND MONTH(date(file_log.created_at)) = $month");
//                if($sub){
//                    $model = $model->andWhere("user.subscription_plan = 'basic' AND user.subscription_expiry > NOW()");
//                }
            $model = $model->createCommand()
                ->queryAll();

            //->one();

        } else {
            $model = FileLog::find()
                ->select([
                    new Expression('count(*) as user_count'),
                    'file_id',
                    'file_log.class_id',
                    'user_id',
                    'vc.content_id'
                ])
                ->leftJoin('video_content vc', 'vc.id = file_log.file_id')
                ->leftJoin('user', 'user.id = file_log.user_id')
                ->groupBy(['file_id'])
                ->where(['source' => 'catchup'])
                ->andWhere("YEAR(date(file_log.created_at)) = $year AND MONTH(date(file_log.created_at)) = $month")
                ->createCommand()
                ->queryAll();

        }
        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        return $this->render('wizitup-stat', ['data' => $model, 'type' => $type]);
        //       return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, count($model) . ' records found');
    }

    public function actionBbb()
    {
        $model = new BigBlueButtonModel();
        $model->meetingID = '34juet71iypqtqhswj4qhjayjkfm59';
        return $model->GetRecordings();
    }

    public function actionScoreQuiz()
    {
        //return QuizSummaryDetails::find()->where(['=', 'selected', new Expression('`answer`')])->count();
        QuizSummaryDetails::updateAll(['is_correct' => 0, 'score' => 1], ['!=', 'selected', new Expression('`answer`')]);
        QuizSummaryDetails::updateAll(['is_correct' => 1, 'score' => 1], ['=', 'selected', new Expression('`answer`')]);
    }

    public function actionOlderLiveClass()
    {

        $model = new BigBlueButtonModel();
        $data = $model->GetRecordings();

        if (!isset($data['recordings'])) {
            return null;
        }
        foreach ($data['recordings']['recording'] as $meeting) {

            if (!$tutorSession = TutorSession::find()->where(['meeting_room' => $meeting['meetingID'], 'status' => 'completed'])->one()) {
                continue;
            }

            if (!empty($tutorSession->recording)) {
                continue;
            }
            //$tutorSession = TutorSession::findOne(['meeting_room' => $meetingID]);
            $tutorSession->recording = $meeting ?? null;
            if ($tutorSession->save() && !empty($tutorSession->recording)) {
                $playback = $meeting['playback']['format'];
                if (!PracticeMaterial::find()->where(['filename' => $playback['url']])->exists()) {
                    $tutorSession = TutorSession::find()->where(['meeting_room' => $meeting['meetingID']])->one();
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
                        continue;
                       // return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid validation while saving video');
                    }
                    $model->saveFileFeed($tutorSession->class);
//                    return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Video successfully saved');
                }

            }
        }
    }


}

