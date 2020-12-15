<?php

namespace app\modules\v2\controllers;


use app\modules\v2\components\Recommendation;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\GenerateString;
use app\modules\v2\models\PracticeMaterial;
use app\modules\v2\models\Recommendations;
use app\modules\v2\models\SchoolCalendar;
use app\modules\v2\models\User;
use app\modules\v2\models\VideoContent;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Media\Video;
use Yii;
use yii\helpers\ArrayHelper;
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

        $ffmpeg = FFMpeg::create(
            [
                'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe'
            ]
        );
        $video = $ffmpeg->open('https://s3.eu-west-2.amazonaws.com/recordings.gradely.ng/recordings/td6z7ljkhhwdkfwuu54jyt2zs36kd7/td6z7ljkhhwdkfwuu54jyt2zs36kd7_2020-11-11-20-11-46.mp4');
        $frame = $video->frame(TimeCode::fromSeconds(10));
        print_r($frame->save('image.jpg'));


//        $im = new \Imagick('file.pdf[0]');
//        $im->setImageFormat('jpg');
//        header('Content-Type: image/jpeg');
//        echo $im;
    }


}

