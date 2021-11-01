<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\Adaptivity;
use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\components\Pricing;
use app\modules\v2\components\Recommendation;
use app\modules\v2\models\Classes;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\Feed;
use app\modules\v2\models\FeedLike;
use app\modules\v2\models\FileLog;
use app\modules\v2\models\games\GameLike;
use app\modules\v2\models\games\GameLog;
use app\modules\v2\models\games\Games;
use app\modules\v2\models\games\Subject;
use app\modules\v2\models\HomeworkQuestions;
use app\modules\v2\models\Parents;
use app\modules\v2\models\PracticeTopics;
use app\modules\v2\models\Questions;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\SubjectImage;
use app\modules\v2\models\VideoAssign;
use app\modules\v2\models\VideoContent;
use app\modules\v2\student\models\StartDiagnosticForm;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use app\modules\v2\models\{Homeworks,
    ApiResponse,
    FeedComment,
    PracticeMaterial,
    Catchup,
    SubjectTopics,
    QuizSummary,
    QuizSummaryDetails,
    Subjects,
    Recommendations,
    RecommendationTopics,
    User,
    RecommendedResources,
    TutorSession
};
use app\modules\v2\student\models\{StartPracticeForm, StartQuizSummaryForm};
use app\modules\v2\components\{SharedConstant, Utility, SessionTermOnly, SessionWeek};


/**
 * Schools/Parent controller
 */
class CatchupController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Catchup';
    private $subject_array = array();
    private $single_topic_array = array('type' => SharedConstant::SINGLE_TYPE_ARRAY);
    private $mix_topic_array = array();
    private $homeworks_topics;
    private $topics = array();
    private $weekly_recommended_topics = array();
    private $daily_recommended_topics = array();
    private $subjects;

    /**
     * @return array
     */
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
            'class' => CustomHttpBearerAuth::className(),
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['view']);
        return $actions;
    }

    /**
     * Return student previous practices
     * @return ApiResponse
     */
    public function actionRecentPractice()
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_STUDENT) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission failed');
        }

        $models = Homeworks::find()
            ->innerJoin('quiz_summary', 'quiz_summary.homework_id = homeworks.id')
            ->where(['homeworks.student_id' => Yii::$app->user->id, 'quiz_summary.submit' => SharedConstant::VALUE_ONE])
            ->andWhere(['<>', 'quiz_summary.type', SharedConstant::PRACTICE_TYPES[2]])
            ->orderBy(['quiz_summary.id' => SORT_DESC]);

        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $models,
            'pagination' => [
                'pageSize' => 6,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found');

    }

    /**
     * Get comments on a video
     * @param $video_token
     * @return ApiResponse
     */
    public function actionVideoComments($video_token)
    {
        $video = VideoContent::findOne(['token' => $video_token]);
        if (!$video)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video not found');

        $model = FeedComment::find()
            ->where(['feed_id' => $video->id, 'type' => SharedConstant::TYPE_VIDEO
                //    , 'user_id' => Yii::$app->user->id
            ])
            ->limit(20)
            ->orderBy('id DESC')
            ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Comments not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    /**
     * Comment on a video
     *
     * @return ApiResponse
     */
    public function actionCommentVideo()
    {
        $video_token = Yii::$app->request->post('video_token');
        $comment = Yii::$app->request->post('comment');
        $type = 'video';
        $form = new \yii\base\DynamicModel(compact('video_token', 'comment', 'type'));
        $form->addRule(['video_token', 'comment'], 'required');
        $form->addRule(['video_token'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_token' => 'token']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $video = VideoContent::findOne(['token' => $video_token]);

        $model = new FeedComment;
        $model->attributes = Yii::$app->request->post();
        $model->user_id = Yii::$app->user->id;
        $model->feed_id = $video->id;
        $model->type = $type;
        if (!$model->save(false)) {
            return (new ApiResponse)->error($model->errors, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record saved');
    }

    /**
     * Like or Dislike a video
     * @param $video_token
     * @return ApiResponse
     */
    public function actionVideoLikes($video_token)
    {

        $status = Yii::$app->request->post('status');

        $form = new \yii\base\DynamicModel(compact('video_token', 'status'));
        $form->addRule(['video_token', 'status'], 'required');
        $form->addRule(['status'], 'in', ['range' => [1, 0]]);
        $form->addRule(['video_token'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_token' => 'token']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = VideoContent::findOne(['token' => $video_token, 'content_type' => 'video']);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video is not found!');
        }

        $likeStatus = FeedLike::find()->where(['parent_id' => $model->id, 'user_id' => Yii::$app->user->id, 'type' => 'video']);
        if ($likeStatus->exists()) {
            $likeStatus = $likeStatus->one();
            if ($likeStatus->status != $status) {
                $likeStatus->status = $status;
                if (!$likeStatus->save())
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Unsuccessful!');
            }
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL);
        }

        $modelLike = new FeedLike;
        $modelLike->parent_id = $model->id;
        $modelLike->user_id = Yii::$app->user->id;
        $modelLike->type = 'video';
        $modelLike->status = $status;
        if (!$modelLike->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not successful');
        }

        return (new ApiResponse)->success($modelLike, ApiResponse::SUCCESSFUL);
    }


    public function actionVideo($id)
    {
        $model = PracticeMaterial::find()
            ->where(['id' => $id, 'filetype' => SharedConstant::TYPE_VIDEO])
            ->one();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    /**
     * This recommend resources in a class
     * @param $class_id
     * @return ApiResponse
     */
    public function actionClassResources($class_id)
    {
        $model = PracticeMaterial::find()
            ->innerJoin('feed', 'feed.id = practice_material.practice_id')
            ->where(['feed.class_id' => $class_id])
            ->orderBy(['feed.updated_at' => SORT_DESC]);

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => 12,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found');

    }

    /**
     * This returned videos that has been watched by a student and recommend them to be watched again.
     * @param $id
     * @return ApiResponse
     */
    public function actionWatchVideoAgain($id)
    {

        $file_log_id = FileLog::find()
            ->innerJoin('video_content', 'video_content.id = file_log.file_id')
            ->andWhere([
                'is_completed' => SharedConstant::VALUE_ONE,
                'id' => $id,
                'user_id' => Yii::$app->user->id
            ])
            ->limit(6)
            ->one();

        if (!$file_log_id) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($file_log_id, ApiResponse::SUCCESSFUL, 'Watch Video Again');

    }

    /**
     * This return last six videos watched by a student
     * @return ApiResponse
     */
    public function actionVideosWatched($child = null, $all = 0)
    {
        $studentID = Utility::getParentChildID();

        $file_log = FileLog::find()
            ->where([
                'is_completed' => SharedConstant::VALUE_ONE,
                'user_id' => $studentID,
                'type' => SharedConstant::TYPE_VIDEO,
                'class_id' => [Utility::ParentStudentChildClass($child), Utility::ParentStudentChildClass($child, 0)]
            ])
            ->groupBy('file_id')
            ->orderBy('id DESC');

        if (!$file_log) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Records not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $file_log,
            'pagination' => [
                'pageSize' => $all == 0 ? 6 : 9,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, null, $provider);

    }

    /**
     * This return details of videos to be watched
     * @param $video_token
     * @return ApiResponse
     */
    public function actionWatchVideo($video_token)
    {

        $form = new \yii\base\DynamicModel(compact('video_token'));
        //$form->addRule(['video_token'], 'exist', ['targetClass' => VideoAssign::className(), 'targetAttribute' => ['video_id' => 'content_id']]);
        $form->addRule(['video_token'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_token' => 'token']]);

        $studentID = Utility::getParentChildID();
        if (!$form->validate() || !Pricing::SubscriptionStatus(null, $studentID)) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $video = VideoContent::find()->where(['token' => $video_token])
            //->with(['views'])
            ->one();
        if ($video->owner == 'wizitup') {
            $videoObject = Utility::GetVideo($video->content_id);
            $videoUrl = isset($videoObject->data->content_link) ? $videoObject->data->content_link : null;
        } else {
            $videoUrl = $video->path;
        }

        if ($file_log = FileLog::findOne([
            'is_completed' => SharedConstant::VALUE_ZERO,
            'user_id' => Yii::$app->user->id,
            'type' => SharedConstant::TYPE_VIDEO,
            'file_id' => $video->id,
        ])) {
            $currentDuration = $file_log->current_duration;
        } else {
            $currentDuration = 0;
        }

        if (!empty($video->new_title))
            $video->title = $video->new_title;

        $video = array_merge(
            ArrayHelper::toArray($video),
            [
                'url' => $videoUrl,
                'assign' => $video->videoAssigned,
                'topic' => $video->videoAssigned->topic,
                'subject' => $video->videoAssigned->topic->subject,
                'currentDuration' => $currentDuration
            ]);


        if (!$video) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video not found');
        }

        return (new ApiResponse)->success($video, ApiResponse::SUCCESSFUL, 'Video Found');

    }

    /**
     * This update video that is being watched by a child to completely watched.
     * This function enable us to recommend Watch Again videos
     * @param $video_token
     * @return ApiResponse
     */
    public function actionUpdateVideoCompleted($video_token, $source = 'catchup')
    {
        $class_id = Utility::getStudentClass();;

        $duration = Yii::$app->request->post('duration');

        $form = new \yii\base\DynamicModel(compact('video_token', 'class_id', 'duration'));
        $form->addRule(['video_token', 'duration'], 'required');
        if ($source == 'feed') {
            $form->addRule(['video_token'], 'exist', ['targetClass' => PracticeMaterial::className(), 'targetAttribute' => ['video_token' => 'token']]);
        } else {
            $form->addRule(['video_token'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_token' => 'token']]);
        }
        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');


        if ($source == 'feed') {
            $video = PracticeMaterial::findOne(['token' => $video_token]);
        } else {
            $video = VideoContent::findOne(['token' => $video_token]);
        }
        $file_log = FileLog::findOne([
            'is_completed' => SharedConstant::VALUE_ZERO,
            'user_id' => Yii::$app->user->id,
            'type' => SharedConstant::TYPE_VIDEO,
            'file_id' => $video->id,
        ]);

        if (!$file_log)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video not found');


        $file_log->current_duration = $duration;
        $file_log->is_completed = SharedConstant::VALUE_ONE;


        if (!$file_log->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video not found');
        }


        return (new ApiResponse)->success($file_log, ApiResponse::SUCCESSFUL, 'Video updated');

    }

    /**
     * This is to update position of video that is being watched by a student.
     * This record allow us to recommended videos that was not completely watched by a child.
     *
     * @param $video_token
     * @return ApiResponse
     */
    public function actionUpdateVideoLength($video_token, $source = 'catchup')
    {

        if ($source == 'feed') {
            $video = PracticeMaterial::findOne(['token' => $video_token]);

            if (!$video)
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video not found');

            $video_id = $video->id;
            $duration = Yii::$app->request->post('duration');
            $form = new \yii\base\DynamicModel(compact('duration', 'video_id'));
            $form->addRule(['duration'], 'required');
            $form->addRule(['duration'], 'integer');

            if (!$form->validate())
                return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');

            $model = FileLog::find()
                ->andWhere([
                    'is_completed' => SharedConstant::VALUE_ZERO,
                    'file_id' => $video_id,
                    'type' => SharedConstant::TYPE_VIDEO,
                    'user_id' => Yii::$app->user->id
                ])
                ->one();

            if (!$model) {
                $model = new FileLog;
                $model->user_id = Yii::$app->user->id;
                $model->file_id = $video_id;
                $model->type = SharedConstant::TYPE_VIDEO;
                //$model->subject_id = $video->topic->subject_id;
                $model->class_id = Utility::getStudentClass();

//                if($video->feed){
//                    $model->subject_id = $video->feed->subject_id;
//                }
                //$model->topic_id = $video->topic_id;
                $model->source = $source;
                //$model->class_id = Utility::getStudentClass();
                //$model->total_duration = $video->content->content_length;
                if (!$model->validate()) {
                    return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
                }
                $model->current_duration = $duration;
                if (!$model->save()) {
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video record not saved!');
                }

                return (new ApiResponse)->success(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video record saved!');
            }

            $model->current_duration = $duration;
            if (!$model->save()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video duration not updated');
            }


            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Video duration updated');
        }

        $video = VideoContent::findOne(['token' => $video_token]);
        if (!$video)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video not found');

        $video_id = $video->id;
        $duration = Yii::$app->request->post('duration');
        $form = new \yii\base\DynamicModel(compact('duration', 'video_id'));
        $form->addRule(['duration'], 'required');
        $form->addRule(['duration'], 'integer');
        $form->addRule(['video_id'], 'exist', ['targetClass' => VideoAssign::className(), 'targetAttribute' => ['video_id' => 'content_id']]);

        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');

        $video = VideoAssign::findOne(['content_id' => $video_id]);

        $model = FileLog::find()
            ->andWhere([
                'is_completed' => SharedConstant::VALUE_ZERO,
                'file_id' => $video_id,
                'type' => SharedConstant::TYPE_VIDEO,
                'user_id' => Yii::$app->user->id
            ])
            ->one();

        //This update status of watched daily video recommendation to taken(true)
        if ($recommendedResources = RecommendationTopics::find()
            //->andWhere('created_at >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)')
            ->where(['student_id' => Yii::$app->user->id, 'object_id' => $video_id, 'object_type' => 'video', 'is_done' => 0])->all()) {
            $recID = ArrayHelper::getColumn($recommendedResources, 'recommendation_id');
            $recResID = ArrayHelper::getColumn($recommendedResources, 'id');

            RecommendationTopics::updateAll(['is_done' => 1], ['id' => $recResID]);
            Recommendations::updateAll(['is_taken' => 1], ['id' => $recID]);
        }


        if (!$model) {
            $model = new FileLog;
            $model->user_id = Yii::$app->user->id;
            $model->file_id = $video_id;
            $model->type = SharedConstant::TYPE_VIDEO;
            $model->subject_id = $video->topic->subject_id;
            $model->topic_id = $video->topic_id;
            $model->source = $source;
            $model->class_id = Utility::getStudentClass();
            $model->total_duration = $video->content->content_length;
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
            }
            $model->current_duration = $duration;
            if (!$model->save()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video record not saved!');
            }

            return (new ApiResponse)->success(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video record saved!');
        }

        $model->current_duration = $duration;
        if (!$model->save()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video duration not updated');
        }


        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Video duration updated');
    }

    /**
     * This returns all the subjects that are available for diagnostics.
     * @return ApiResponse
     */
    public function actionDiagnostic($child = null)
    {
        $class_id = Utility::ParentStudentChildClass($child);

        if (!$class_id) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found');
        }

        $studentID = Utility::getParentChildID();
        $mode = Utility::getChildMode($studentID);
        if ($mode == SharedConstant::EXAM_MODES[1]) {
            $subjects = [];
            foreach (Utility::StudentExamSubjectID($studentID, 'exam_id') as $key => $exam) {
                $model = Subjects::find()
//                    ->select([
//                        'subjects.*',
//                        new Expression('"exam" AS mode'),
//                        'et.name AS exam_name',
//                        'et.id AS exam_id'
//                        //new Expression('CONCAT("https://gradly.s3.eu-west-2.amazonaws.com/exams/",exam_id) as mode'),
//                    ])
                    ->leftJoin('homeworks', "homeworks.subject_id = subjects.id AND homeworks.type = 'diagnostic' AND homeworks.mode = 'exam' AND homeworks.student_id = $studentID AND homeworks.exam_type_id = $exam")
                    ->leftJoin('quiz_summary qs', "qs.subject_id = subjects.id AND qs.type = 'diagnostic' AND qs.mode = 'exam' AND qs.submit = 1 AND qs.student_id = $studentID AND qs.homework_id = homeworks.id AND homeworks.exam_type_id = $exam")
                    ->leftJoin('exam_type et', "et.id = $exam")
                    ->where(['subjects.status' => 1, 'subjects.id' => Utility::StudentExamSubjectID($studentID), 'subjects.school_id' => null, 'et.id' => Utility::StudentExamSubjectID($studentID, 'exam_id')])
                    ->andWhere(['AND', ['is', 'qs.subject_id', null], ['is', 'homeworks.exam_type_id', null]])
                    ->groupBy('subjects.id')
                    //->asArray()
                    ->all();

                foreach ($model as $index => $item) {
                    $examModel = ExamType::find()->select(['id exam_id', 'name exam_name'])->where(['id' => $exam])->asArray()->one();
                    $examImg = SubjectImage::findOne(['subject_id' => $item->id, 'exam_id' => $exam]);
                    $item->image = Utility::AbsoluteImage(isset($examImg->image) ? $examImg->image : null, 'subject');
                    $model[$index] = array_merge(ArrayHelper::toArray($item), $examModel);
                }
                $subjects = array_merge($subjects, $model);
            }
            $model = $subjects;
        } else {
            $model = Subjects::find()
                //->select(['subjects.*', new Expression('"practice" AS mode')])
                ->leftJoin('quiz_summary qs', "qs.subject_id = subjects.id AND qs.type = 'diagnostic' AND qs.mode = 'practice' AND qs.submit = 1 AND student_id = $studentID")
                ->where(['status' => 1, 'diagnostic' => 1, 'school_id' => null, 'category' => array_merge(['all'], Utility::getStudentClassCategory($class_id))])
                ->andWhere(['is', 'qs.subject_id', null])
                ->groupBy('id')
                //->asArray()
                ->all();
        }

        $attemptStatus = QuizSummary::find()->where(['student_id' => $studentID, 'submit' => 1, 'mode' => $mode])->exists() ? true : false;
        $attemptStatus = ['takenPractice' => $attemptStatus];

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subject not found', $attemptStatus);
        }


        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, count($model) . ' subjects found', null, $attemptStatus);
    }

    /**
     * The practices that was recently taken by a child
     * @return ApiResponse
     */
    public function actionRecentPractices($child = null, $all = 0)
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[2] && Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }


        $student_id = Utility::getParentChildID();
        $mode = Utility::getChildMode($student_id);
        $models = QuizSummary::find()
            ->alias('qs')
            ->select([
                'qs.id',
                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(hq.id))*100) as score'), 'qsd.topic_id',
                'qs.homework_id',
                'qs.subject_id',
                'qs.student_id',
                'qs.correct',
                'qs.failed',
                'qs.total_questions',
                new Expression('DATE(qs.created_at) as date'),
            ])
            ->innerJoin('practice_topics pt', "pt.practice_id = qs.homework_id")
            ->innerJoin('quiz_summary_details qsd', "qsd.student_id = qs.student_id AND qsd.quiz_id = qs.id")
            ->innerJoin('homework_questions hq', 'hq.homework_id = qs.homework_id')
            ->where([
                'qs.student_id' => $student_id,
                'submit' => SharedConstant::VALUE_ONE, 'mode' => $mode])
            ->andWhere(['<>', 'type', SharedConstant::QUIZ_SUMMARY_TYPE[0]])
            ->orderBy(['submit_at' => SORT_DESC])
            ->groupBy(['qs.id'])
            ->asArray()
            ->all();

        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice not found');
        }

        $final = [];
        foreach ($models as $model) {
            $topics = SubjectTopics::find()
                ->innerJoin('practice_topics pt', "pt.practice_id = {$model['homework_id']} AND pt.topic_id = subject_topics.id")
                ->all();
            $final[] = array_merge($model, ['tag' => count($topics) > 1 ? 'mix' : 'single', 'topics' => $topics]);
        }

        if ($all == 1) {
            $uniqueData = ArrayHelper::getColumn($final, 'date');
            $dates = array_unique($uniqueData);
            $bothFinal = [];
            foreach ($dates as $k => $date) {
                $tempData = [];
                foreach ($final as $y => $element) {
                    if ($date == $element['date']) {
                        $tempData[] = $element;
                    }
                }
                $bothFinal[] = ['date' => $date, 'data' => $tempData];
            }
            $final = $bothFinal;
        }
        $provider = new ArrayDataProvider([
            'allModels' => $final,
            'pagination' => [
                'pageSize' => $all == 0 ? 6 : 12,
                'validatePage' => false,
            ],
        ]);
        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Recent practices found', $provider);
    }

    /**
     * This returns videos not completely watched by student in a class
     * @return ApiResponse
     */
    public function actionIncompleteVideos($child = null, $all = 0)
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3] && Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[2]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $student_id = $studentID = Utility::getParentChildID();

        $model = FileLog::find()->where([
            'user_id' => $student_id,
            'is_completed' => SharedConstant::VALUE_ZERO
        ])->orderBy('id DESC');

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Videos not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => $all == 0 ? 6 : 12,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Videos found', $provider);
    }

    /**
     * This returns all materials in a class
     * Also known as Resources
     * @return ApiResponse
     */
    public function actionClassMaterials()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }
        $class_id = Utility::getStudentClass();

        $model = PracticeMaterial::find()
            //->innerJoin('homeworks', "homeworks.id = practice_material.practice_id")//removed
            ->innerJoin('homeworks', "homeworks.id = practice_material.practice_id AND homeworks.class_id = $class_id")
            //->where(['user_id' => Yii::$app->user->identity->id])//remogit pullved
            ->where(['class_id' => Utility::getStudentClass()])
            ->andWhere([
                'filetype' => [
                    SharedConstant::PRACTICE_MATERIAL_TYPES[0],
                    SharedConstant::PRACTICE_MATERIAL_TYPES[1]
                ]
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(12)
            ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    /**
     * This action generates recommended topic for catchup.
     * @return ApiResponse
     */
    public function actionPracticeRecommendations($child = null, $subject = null)
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3] && Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[2]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $class_id = Utility::ParentStudentChildClass($child);
        $student_id = Utility::getParentChildID();
        $mode = Utility::getChildMode($student_id);
        $models = QuizSummary::find()
            ->select('subject_id')
            ->where(['submit' => 1,
                'quiz_summary.student_id' => $student_id,
                'mode' => $mode
            ])
            ->andWhere(['AND',
                //['<>', 'type', 'recommendation'],
                ['<>', 'type', 'catchup']
            ])
            ->innerJoin('quiz_summary_details qsd', 'qsd.quiz_id = quiz_summary.id')
            ->groupBy('subject_id');
        if (!empty($subject)) {
            $models = $models->andWhere(['subject_id' => $subject]);
        }
        $models = $models->asArray()
            ->all();

        $finalResult = [];
        foreach ($models as $model) {
            $topics = QuizSummaryDetails::find()
                ->select('quiz_summary_details.topic_id')
                ->innerJoin('quiz_summary', "quiz_summary.id = quiz_summary_details.quiz_id AND quiz_summary.mode = '$mode'")// AND quiz_summary.type != 'recommendation'
                ->innerJoin('subject_topics st', "st.id = quiz_summary_details.topic_id")
                ->where([
                    'quiz_summary.subject_id' => $model['subject_id'],
                    'quiz_summary_details.student_id' => $student_id
                ])
                ->groupBy('quiz_summary_details.topic_id')
                ->orderBy('quiz_summary_details.id DESC')
                ->all();

            //Get 1 new topic that has not been attempted before


            $oneSubject = Subjects::find()->select(['id', 'slug', 'name', 'status', Yii::$app->params['subjectImage']])
                ->where(['id' => $model['subject_id']])->asArray()->one();

            $practicedTopicIds = ArrayHelper::getColumn($topics, 'topic_id');

            $topicModels = QuizSummaryDetails::find()
                ->alias('qsd')
                ->select([
                    new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                    'COUNT(qsd.id) as total',
                    'SUM(case when qsd.selected = qsd.answer then 1 else 0 end) as correct',
                    'st.topic',
                    'st.id',
                    'st.subject_id',
                    Utility::ImageQuery('st'),
                ])
                ->innerJoin('subject_topics st', "st.id = qsd.topic_id AND st.subject_id = {$model['subject_id']} AND st.class_id = $class_id")
                ->innerJoin('exam_type', "exam_type.id = st.exam_type_id AND exam_type.is_catchup = 1")
                ->innerJoin('questions q', 'q.topic_id = qsd.topic_id')
                ->innerJoin('quiz_summary', "quiz_summary.id = qsd.quiz_id AND quiz_summary.mode = '$mode'")
                ->where([
                    'qsd.topic_id' => $practicedTopicIds,
                    'qsd.student_id' => $student_id,
                    'st.subject_id' => $model['subject_id']
                ])
                ->orderBy('score')
                ->asArray()
                ->groupBy('qsd.topic_id')
                ->limit(10)
                ->all();

            if ($oneNewTopic = SubjectTopics::find()
                ->select([
                    new Expression('null as score'),
                    new Expression('null as total'),
                    new Expression('null as correct'),
                    'topic',
                    'subject_topics.id',
                    'subject_topics.subject_id',
                    Utility::ImageQuery('subject_topics'),
                ])
                ->where(['subject_topics.class_id' => $class_id, 'subject_topics.subject_id' => $model['subject_id']])
                ->andWhere(['NOT IN', 'subject_topics.id', $practicedTopicIds])
                ->innerJoin('questions q', 'q.topic_id = subject_topics.id')
                ->innerJoin('exam_type e', 'e.id = subject_topics.exam_type_id AND e.is_catchup = 1')
                ->having('count(q.id) >= ' . Yii::$app->params['topicQuestionsMin'])
                ->asArray()->one()) {

                $topicModels = array_merge([$oneNewTopic], $topicModels);
            }


            //ArrayHelper::getColumn($topicModels,'id');


///This is working, i had to temporarily disable it.
//            $tutor_sessions = TutorSession::find()
//                ->select([
//                    'tutor_session.*',
//                    new Expression("'live_class' as type"),
//                ])
//                ->where([
//                    'student_id' => $student_id,
//                    'subject_id' => $model['subject_id'],
//                    'meta' => SharedConstant::RECOMMENDATION,
//                    'status' => SharedConstant::PENDING_STATUS,
//                    'is_school' => 1
//                ])
//                //Student only sees live_class/remedial that supposed to hold within the next 72hours
//                ->andWhere('availability > DATE_SUB(NOW(), INTERVAL 72 HOUR)')
//                ->asArray()->all();

            if (!empty($subject)) {
                $practices = $this->getRecommendedPractices($student_id, $model, $subject);// recommendations made by teachers
                $topicOrders = Adaptivity::generateSingleMixPractices($topicModels);
                $topicOrders = array_merge($practices, $topicOrders);
                $recommendedVideos = $this->getRecommendedVideos($student_id, $model, $topicModels);

                $topicOrders = array_splice($topicOrders, 0, 6);
                $finalResult = array_merge(
                    $oneSubject,
                    [
                        'practices' => $topicOrders,
                        'videos' => $recommendedVideos
                    ]);
            } else {
                $practices = $this->getRecommendedPractices($student_id, $model, $subject);// recommendations made by teachers
                $topicOrders = Adaptivity::generateSingleMixPractices($topicModels);
                $topicOrders = array_merge($practices, $topicOrders);

                $topicOrders = array_splice($topicOrders, 0, 6);
                if (!empty($topicOrders)) {
                    $finalResult[] = array_merge(
                        $oneSubject,
                        [
                            'topics' => $topicOrders,
                        ]);
                }
            }

        }


        if (!$finalResult) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice Topics not found');
        }


        return (new ApiResponse)->success($finalResult, ApiResponse::SUCCESSFUL, null);

    }

    protected function getRecommendedVideos($student_id, $model, $practices)
    {
        //Get all videos watched by student in specified subject
        $alreadyWatchedVideos = ArrayHelper::getColumn(FileLog::find()->select('file_id')
            ->where(['user_id' => $student_id, 'subject_id' => $model['subject_id'], 'type' => SharedConstant::TYPE_VIDEO, 'is_completed' => 0])->all(),
            'file_id');

        $recommended_videos = RecommendedResources::find()
//                ->select([
//                    'recommended_resources.*',
//                    new Expression("'resource' as type"),
//                ])
            ->where([
                'receiver_id' => $student_id,
                'resources_type' => SharedConstant::TYPE_VIDEO
            ])
            ->andWhere([
                'NOT IN',
                'resources_id',
                $alreadyWatchedVideos
            ])
            ->all();

        // Get actual video object
        $recommendedVideos = VideoContent::find()
            ->select(['*', new Expression("'video' as type"),])
            ->where(['id' => ArrayHelper::getColumn($recommended_videos, 'resources_id')])
            ->leftJoin('video_assign va', 'va.content_id = video_content.id')
            ->andWhere(['va.topic_id' => ArrayHelper::getColumn($practices, 'id')])
            ->asArray()
            ->limit(12)
            ->all();

        return $recommendedVideos;
    }

    /**
     * This is recommendation made by teacher to a student
     * @param $student_id
     * @param $model
     * @return array
     */
    protected function getRecommendedPractices($student_id, $model, $subject)
    {
        $practicesList = Homeworks::find()
            ->select([
                'homeworks.*',
                new Expression("'practice' as type"),
                //new Expression("CONCAT(user.firstname,' ',user.lastname)")
            ])
            ->innerJoin('user', 'user.id = homeworks.teacher_id')
            ->where([
                'student_id' => $student_id,
                'subject_id' => $model['subject_id'],
            ])
            ->andWhere([
                'reference_type' => ['homework', 'class']
            ])
            ->andWhere("
                            NOT EXISTS
                    (
                    SELECT  null 
                    FROM    quiz_summary qs
                    WHERE   qs.homework_id = homeworks.id AND qs.student_id = $student_id AND qs.submit = 1
                    )
                ");
        if (empty($subject))
            $practicesList = $practicesList->limit(4);
        $practicesList = $practicesList->all();

        $practices = [];
        foreach ($practicesList as $practice) {
            if (count($practice->topics) == 1) {
                $duration = SharedConstant::SINGLE_PRACTICE_QUESTION_COUNT;
                $tag = 'single';
            } else {
                $duration = count($practice->topics) * SharedConstant::MIX_PRACTICE_QUESTION_COUNT;
                $tag = 'mix';
            }
            $practices[] = [
                'type' => 'practice',
                'practice_id' => $practice->id,
                'question_duration' => $duration,
                'tag' => $tag,
                'teacher' => $practice->teacher->firstname . ' ' . $practice->teacher->lastname,
                'topics' => $practice->topics
                //'is_done' => $this->practiceStatus($practice),
            ];
        }

        return $practices;
    }

    private function practiceStatus($practice)
    {
        $model = QuizSummary::find()
            ->where([
                'homework_id' => $practice->id,
                'subject_id' => $practice->subject_id,
                'class_id' => $practice->class_id,
                'submit' => 1,
                'type' => 'homework'
            ])
            ->one();

        if ($model) {
            return 1;
        }

        return 0;
    }

    /**
     * This initialize practice is used by student for Catchup Only Practices
     * @return ApiResponse
     */
    public function actionInitializePracticeBK()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $model = new StartPracticeForm;
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }


        if (!$homework_model = $model->initializePractice()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice Initialization failed');
        }

        return (new ApiResponse)->success($homework_model, ApiResponse::SUCCESSFUL, 'Practice Initialization succeeded');
    }

    /**
     * This initialize practice is used by student for Diagnostic and Catchup Practices
     * @return ApiResponse
     */
    public function actionInitializePractice()
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_STUDENT) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }


        $model = new StartPracticeForm;
        $model->attributes = Yii::$app->request->post();
        $model->practice_type = 'recommendation';
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $mode = Utility::getChildMode(Yii::$app->user->id);
        if ($mode == 'exam') {
            if (!Yii::$app->request->post('exam_id') && !Yii::$app->request->get('exam_id')) {
                if ($tempExam = SubjectTopics::find()->where(['subject_topics.id' => $model->topic_ids])
                    //->innerJoin('exam_type', 'exam_type.id = subject_topics.exam_type_id AND exam_type.is_exam = 0')
                    ->one()) {
                    $model->exam_id = $tempExam->exam_type_id;
                } else {
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Exam id is required');
                }
            } else {
                if (!Yii::$app->request->get('exam_id')) {
                    $model->exam_id = Yii::$app->request->get('exam_id');
                } else {
                    $model->exam_id = Yii::$app->request->post('exam_id');
                }
            }
        }

        if (!$homework_model = $model->initializePractice()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice Initialization failed');
        }

        return (new ApiResponse)->success($homework_model, ApiResponse::SUCCESSFUL, 'Practice Initialization succeeded');
    }

    public function actionInitializeDiagnostic()
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_STUDENT) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $model = new StartDiagnosticForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$diagnosticTopics = $model->initializeDiagnostic()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Diagnostic Initialization failed');
        }

        $topicIDs = ArrayHelper::getColumn($diagnosticTopics, 'id');

        $mode = Utility::getChildMode(Yii::$app->user->id);
        // Initialize the practice
        $startPractice = new StartPracticeForm();
        $startPractice->type = 'mix';
        $startPractice->topic_ids = $topicIDs;
        $startPractice->practice_type = 'diagnostic';
        if ($mode == 'exam') {
            if (!Yii::$app->request->post('exam_id')) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Exam id is required');
            }
            $startPractice->exam_id = Yii::$app->request->post('exam_id');
        }


        if (!$homework_model = $startPractice->initializePractice()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Diagnostic Topics Initialization failed');
        }

        return (new ApiResponse)->success($homework_model, ApiResponse::SUCCESSFUL, 'Diagnostic Initialization succeeded');
    }

    public function actionStartPractice()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $model = new StartQuizSummaryForm;
        $model->practice_id = Yii::$app->request->post('practice_id');
        $model->student_id = Yii::$app->user->id;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$practice_model = $model->startPractice()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice not started!');
        }

        return (new ApiResponse)->success($practice_model, ApiResponse::SUCCESSFUL, 'Practice started!');
    }

    /**
     *
     * This returns questions to be attempted
     * @return ApiResponse
     */
    public function actionGetPracticeQuestions()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $practice_id = Yii::$app->request->post('practice_id');
        $student_id = Yii::$app->user->id;
        $form = new \yii\base\DynamicModel(compact('practice_id', 'student_id'));
        $form->addRule(['practice_id', 'student_id'], 'required');
        $form->addRule(['practice_id', 'student_id'], 'integer');
        $form->addRule(['practice_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['student_id', 'practice_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if (Homeworks::find()
            ->alias('hm')
            ->where(['hm.student_id' => $student_id, 'hm.id' => $practice_id, 'qs.student_id' => $student_id])
            ->innerJoin('quiz_summary qs', 'qs.homework_id = hm.id AND qs.submit=1')
            ->exists()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if (!Homeworks::find()
            ->alias('hm')
            ->where(['hm.student_id' => $student_id, 'hm.id' => $practice_id])
            ->innerJoin('practice_topics pt', 'pt.practice_id = hm.id')
            ->exists()) {
            $form->addError('practice_id', 'Topics not available, try again.');
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $homework = Homeworks::findOne([['student_id' => $student_id, 'id' => $practice_id]]);

        $model = new StartQuizSummaryForm;
        if (!$practice_model = $model->getTotalQuestions($homework)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice not started!');
        }

        return (new ApiResponse)->success($practice_model, ApiResponse::SUCCESSFUL, 'Practice started!');
    }

    /**
     * Because diagnostic and practice questions are not pre-generated, we save their attempted questions records on submission
     * @param $practice_id
     * @param $student_id
     * @param $question
     * @return bool
     */
    private function saveHomeworkQuestion($practice_id, $student_id, $question)
    {
        $model = new HomeworkQuestions();
        $model->homework_id = $practice_id;
        $model->teacher_id = $student_id;
        $model->question_id = $question->id;
        $model->duration = $question->duration;
        $model->difficulty = $question->difficulty;
        if ($model->save())
            return true;

        return false;
    }


    /**
     * This process diagnostic and practice attempts
     * @return ApiResponse
     */

    public function actionAssessmentRecommendation($id = null)
    {
        $model = RecommendationTopics::find()
            ->innerJoin('recommendations', 'recommendations.id = recommendation_topics.recommendation_id');

        if (!empty($id)) {
            $model = $model->where(['recommendation_topics.recommendation_id' => $id])->all();
        } else {
            $model = $model
                ->andWhere(['recommendations.student_id' => Yii::$app->user->id])
                ->limit(6)
                ->orderBy(['created_at' => SORT_DESC])
                ->all();
        }

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Recommendation not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Recommendation found');
    }

    public function actionSubmitPractice()
    {

        $attempts = \Yii::$app->request->post('attempts');
        $practice_id = \Yii::$app->request->post('practice_id');

        $student_id = \Yii::$app->user->id;

        $failedCount = 0;
        $correctCount = 0;

        $model = new \yii\base\DynamicModel(compact('attempts', 'practice_id', 'student_id'));
        $model->addRule(['attempts', 'practice_id'], 'required')
            ->addRule(['practice_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['practice_id' => 'id', 'student_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice not validated');
        }

        if (!is_array($attempts)) {
            //return error that questions is invalid
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question must be array');
        }

        if (QuizSummary::find()->where(['homework_id' => $practice_id, 'student_id' => \Yii::$app->user->id, 'submit' => 1])->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice already submitted');
        }

        //use transaction before saving;
        $dbtransaction = \Yii::$app->db->beginTransaction();
        try {

            $homework = Homeworks::findOne(['id' => $practice_id, 'student_id' => $student_id]);

            $quizSummary = new QuizSummary();
            $quizSummary->homework_id = $practice_id;
            $quizSummary->attributes = \Yii::$app->request->post();
            if ($homework->teacher_id)
                $quizSummary->teacher_id = $homework->teacher_id;
            $quizSummary->student_id = \Yii::$app->user->id;
            $quizSummary->class_id = Utility::getStudentClass();
            $quizSummary->subject_id = $homework->subject_id;
            $quizSummary->term = Utility::getStudentTermWeek('term');
            $quizSummary->total_questions = count($attempts);
            $quizSummary->type = $homework->type;
            $quizSummary->mode = $homework->mode;
            if (!$quizSummary->validate()) {
                return (new ApiResponse)->error($quizSummary->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice not validated');
            }


            if (!$quizSummary->save()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save your attempt');
            }

            foreach ($attempts as $question) {
                if (!isset($question['selected']) || !isset($question['question']) || !isset($question['time_spent']))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Attempt data is not valid');


                if (in_array($quizSummary->type, ['multiple', 'bool']) && !in_array($question['selected'], SharedConstant::QUESTION_ACCEPTED_OPTIONS))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, "Invalid option '{$question['selected']}' provided");

                $qsd = new QuizSummaryDetails();
                $qsd->quiz_id = $quizSummary->id;
                $qsd->question_id = $question['question'];
                $qsd->selected = (string)$question['selected'];
                if (!$questionModel = Questions::findOne(['id' => $question['question']]))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not valid');
                $qsd->answer = $questionModel->answer;
                $qsd->topic_id = $questionModel->topic_id;
                $qsd->student_id = \Yii::$app->user->id;
                $qsd->homework_id = $quizSummary->homework_id;
                $qsd->time_spent = $question['time_spent'];

//                if ($question['selected'] != $questionModel->answer)
//                    $failedCount = $failedCount + 1;
//
//                if ($question['selected'] == $questionModel->answer)
//                    $correctCount = $correctCount + 1;

                if (in_array($questionModel->type, ['short', 'essay'])) {
                    if ($questionModel->type == 'short') {
                        $answers = json_decode($questionModel->answer);
                        $isScore = false;
                        foreach ($answers as $item) {
                            if (strtolower($item) == strtolower($question['selected'])) {
                                $correctCount = $correctCount + 1;
                                $qsd->is_correct = 1;
                                $isScore = true;
                                break;
                            }
                        }
                        if (!$isScore) {
                            $failedCount = $failedCount + 1;
                            $qsd->is_correct = 0;
                        }
                    }
                } else {
                    if ($question['selected'] == $questionModel->answer) {
                        $correctCount = $correctCount + 1;
                        $qsd->is_correct = 1;
                    } else {
                        $failedCount = $failedCount + 1;
                        $qsd->is_correct = 0;
                    }
                }

                if (!$qsd->save() || !$this->saveHomeworkQuestion($practice_id, $student_id, $questionModel))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'One or more attempt not saved');

            }

            $quizSummary->failed = $failedCount;
            $quizSummary->correct = $correctCount;
            $quizSummary->skipped = $quizSummary->total_questions - ($correctCount + $failedCount);
            $quizSummary->submit = SharedConstant::VALUE_ONE;
            $quizSummary->submit_at = date('Y-m-d H:i:s');
            if (!$quizSummary->save())
                return (new ApiResponse)->error($quizSummary, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Score not saved');

            if ($homework->reference_type == 'daily' && $recommendationObject = Recommendations::findOne(['id' => $homework->reference_id, 'category' => $homework->reference_type, 'student_id' => $homework->student_id])) {
                $recommendationObject->is_taken = 1;
                $recommendationObject->update();
            }

            if ($homework->type == 'diagnostic' && !Utility::StudentRecommendedTodayStatus()) {
                $recommendation = new Recommendation();
                $recommendation->dailyRecommendation($homework->student_id);
                //(new Utility)->generateRecommendation($quizSummary->id);
            }

            $dbtransaction->commit();
            return (new ApiResponse)->success($quizSummary, ApiResponse::SUCCESSFUL, 'Practice processing completed');
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Attempt was not successfully processed');
        }
    }

    /**
     * Explore for student
     * @param null $child
     * @param int $all
     * @return ApiResponse
     */
    public function actionExplore($child = null, $all = 0)
    {
        $classID = Utility::ParentStudentChildClass($child);
        $studentID = Utility::getParentChildID();
        $query1 = (new \yii\db\Query())
            ->from('practice_material pm')
            //->alias('pm')
            ->select([
                'pm.id',
                'pm.title',
                'pm.extension',
                'pm.filetype',
                'pm.filesize',
                new Expression(Pricing::SubscriptionStatus(null, $studentID) ? 'pm.filename as url' : 'null as url'),
                'pm.downloadable',
                //'pm.thumbnail',
                Utility::ThumbnailQuery('pm', 'document'),
                'pm.token',
                "CONCAT(user.firstname,' ',user.lastname) AS creator_name",
                "user.image as creator_image",
                "user.id as creator_id",
                "pm.created_at",
            ])
            ->leftJoin('user', "user.id = pm.user_id")
            ->innerJoin('feed', "feed.id = pm.practice_id AND pm.type = 'feed'")
            ->where([
                'feed.global_class_id' => $classID,
                'pm.filetype' => ['video', 'document'],
                'pm.user_id' => 0 // I added this to exclude real user upload.
            ]);

        $query2 = (new \yii\db\Query())
            ->from('video_content vc')
            ->select([
                'vc.id',
                new Expression('vc.new_title COLLATE utf8mb4_unicode_ci as title'),
                new Expression("'mp4' as extension"),
                new Expression('vc.content_type COLLATE utf8mb4_unicode_ci as filetype'),
                new Expression('vc.content_length COLLATE utf8mb4_unicode_ci as filesize'),
                new Expression('null as url'),
                new Expression("0 as downloadable"),
                new Expression('vc.image COLLATE utf8mb4_unicode_ci as thumbnail'),
                new Expression('vc.token COLLATE utf8mb4_unicode_ci'),
                new Expression("UCASE(owner) AS creator_name"),
                new Expression("null as creator_image"),
                new Expression("null as creator_id"),
                "vc.created_at"
            ])
            ->innerJoin('video_assign va', 'va.content_id = vc.id')
            ->innerJoin('subject_topics st', 'st.id = va.topic_id')
            ->where(['st.class_id' => $classID]);


        // Union table A and B
        $query1->union($query2);


        $query = PracticeMaterial::find()->select('*')->from(['random_name' => $query1]);

        if ($all == 0) {
            $query = $query->limit(12);
        }

        $provider = new ActiveDataProvider([
            'query' => $query->asArray()->orderBy('rand()'),
            'pagination' => [
                'pageSize' => $all == 0 ? 12 : 20,
                'validatePage' => false,
            ],
        ]);


        $games = Games::find()
            ->select(
                [
                    'game_id as id',
                    new Expression('game_title as title'),
                    new Expression("'game' as extension"),
                    new Expression('"game" as filetype'),
                    new Expression('null as filesize'),
                    new Expression('null as url'),
                    new Expression("0 as downloadable"),
                    new Expression('image as thumbnail'),
                    new Expression('token'),
                    new Expression("provider AS creator_name"),
                    new Expression("'https://gradly.s3.eu-west-2.amazonaws.com/placeholders/9ijakid-logo.png' as creator_image"),
                    new Expression("null as creator_id"),
                    "created_at"
                ])
            ->asArray()
            ->where(['status' => 1])
            ->orderBy('rand()')
            ->limit(2)->all();

        $explore = array_merge($provider->getModels(), $games);

        shuffle($explore);
        $explore = array_splice($explore, 0, $all == 0 ? 12 : 20);

        return (new ApiResponse)->success($explore, ApiResponse::SUCCESSFUL, null, $provider);
        //return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, null, $provider);
    }

    public function actionGame($token)
    {
        $game = Games::find()->select([
            'game_id',
            'slug',
            'game_title', 'provider', 'image', 'token',
            new Expression('CONCAT("https://partners.9ijakids.com/index.php/play?partnerId=247807&accessToken=5f63d1c5-3f00-4fa5-b096-9ffd&userPassport=support@gradely.ng&action=play&gameID=",game_id) as game'),
            new Expression('"https://gradly.s3.eu-west-2.amazonaws.com/placeholders/9ijakid-logo.png" as logo'),
            new Expression('(SELECT count(*) FROM game_like gl where gl.game_id = games.game_id AND gl.status = 1) as likes'),
            new Expression('(SELECT count(*) FROM game_like gl where gl.game_id = games.game_id AND gl.status = 0) as dislikes'),
        ])
            ->where(['token' => $token])
            ->asArray()
            ->one();
        if (!$game) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid token');
        }


        $myLike = GameLike::find()->where(['user_id' => Utility::getParentChildID(), 'game_id' => $game['game_id']])->one();
        if (!empty($myLike)) {
            $status = $myLike->status == 1 ? "like" : "dislike";
        } else {
            $status = null;
        }
        unset($game['game_id']);
        return (new ApiResponse)->success(array_merge($game, ['mylike' => $status]));
    }

    public function actionGameLink($token)
    {
        if (!$game = Games::findOne(['token' => $token])) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid token');
        }

        $model = new GameLog();
        $model->game_id = $game->game_id;
        $model->user_id = Utility::getParentChildID();
        $model->save();

        $link = "https://partners.9ijakids.com/index.php/play?partnerId=247807&accessToken=5f63d1c5-3f00-4fa5-b096-9ffd&userPassport=support@gradely.ng&action=play&gameID=" . $game->game_id;
        return (new ApiResponse)->success($link);
    }

    public function actionGameLike($token, $like)
    {
        if (!$game = Games::findOne(['token' => $token])) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid token');
        }
        $studentID = Utility::getParentChildID();
        if (!GameLike::findOne(['game_id' => $game->game_id, 'user_id' => $studentID])) {
            $model = new GameLike();
            $model->user_id = $studentID;
            $model->game_id = $game->game_id;
            $model->status = $like;
            if ($model->save()) {
                return (new ApiResponse)->success(true);
            }
        } else {
            $model = GameLike::findOne(['game_id' => $game->game_id, 'user_id' => $studentID]);
            if ($model->status != $like) {
                $model->status = $like;
                if ($model->save()) {
                    return (new ApiResponse)->success(true);
                }
            }
            return (new ApiResponse)->success(false);
        }
    }
}