<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\models\Classes;
use app\modules\v2\models\Feed;
use app\modules\v2\models\FeedLike;
use app\modules\v2\models\FileLog;
use app\modules\v2\models\HomeworkQuestions;
use app\modules\v2\models\PracticeTopics;
use app\modules\v2\models\Questions;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\VideoAssign;
use app\modules\v2\models\VideoContent;
use app\modules\v2\student\models\StartDiagnosticForm;
use Yii;
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
            //->where(['feed_id' => $video->id, 'type' => SharedConstant::TYPE_VIDEO, 'user_id' => Yii::$app->user->id])
            ->limit(10)
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
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

        $model = new FeedLike;
        $model->parent_id = $model->id;
        $model->user_id = Yii::$app->user->id;
        $model->type = 'video';
        $model->status = $status;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not successful');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL);
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
    public function actionVideosWatched()
    {

        $class_id = Utility::getStudentClass();
        $file_log = FileLog::find()
            ->where([
                'is_completed' => SharedConstant::VALUE_ONE,
                'user_id' => Yii::$app->user->id,
                'type' => SharedConstant::TYPE_VIDEO,
                'class_id' => $class_id
            ])
            ->groupBy('file_id')
            ->limit(6)
            ->orderBy('id DESC')
            ->all();

        if (!$file_log) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Records not found');
        }

        return (new ApiResponse)->success($file_log, ApiResponse::SUCCESSFUL, 'Videos Found');

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

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $video = VideoContent::find()->where(['token' => $video_token])
            //->with(['views'])
            ->one();

        $videoObject = Utility::GetVideo($video->id);

        $videoUrl = isset($videoObject->data->content_link) ? $videoObject->data->content_link : null;

        $video = array_merge(
            ArrayHelper::toArray($video),
            [
                'url' => $videoUrl,
                'assign' => $video->videoAssigned,
                'topic' => $video->videoAssigned->topic,
                'subject' => $video->videoAssigned->topic->subject
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
    public function actionUpdateVideoCompleted($video_token)
    {
        $class_id = Utility::getStudentClass();;

        $duration = Yii::$app->request->post('duration');

        $form = new \yii\base\DynamicModel(compact('video_token', 'class_id', 'duration'));
        $form->addRule(['video_token', 'duration'], 'required');
        $form->addRule(['video_token'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_token' => 'token']]);

        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');


        $video = VideoContent::findOne(['token' => $video_token]);

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
    public function actionUpdateVideoLength($video_token)
    {
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');


        $video = VideoAssign::findOne(['content_id' => $video_id]);

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
            $model->subject_id = $video->topic->subject_id;
            $model->topic_id = $video->topic_id;
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
    public function actionDiagnostic()
    {
        $class_id = Utility::getStudentClass(SharedConstant::VALUE_ONE);
        if (!$class_id) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found');
        }

        $studentID = Yii::$app->user->id;
        $model = Subjects::find()
            ->leftJoin('quiz_summary qs', "qs.subject_id = subjects.id AND qs.submit = 1 AND student_id = $studentID")
            ->where(['status' => 1, 'diagnostic' => 1, 'school_id' => null, 'category' => ['all', Utility::getStudentClassCategory($class_id)]])
            ->andWhere(['is', 'qs.subject_id', null])
            ->groupBy('id')
            ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subject not found');
        }


        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, count($model) . ' subjects found');
    }

    /**
     * The practices that was recently taken by a child
     * @return ApiResponse
     */
    public function actionRecentPractices()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $models = QuizSummary::find()
            ->where(['student_id' => Yii::$app->user->id, 'submit' => SharedConstant::VALUE_ONE])
            ->andWhere(['<>', 'type', SharedConstant::QUIZ_SUMMARY_TYPE[0]])
            ->orderBy(['submit_at' => SORT_DESC])
            ->limit(6)
            ->asArray()
            ->all();

        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice not found');
        }

        $final = [];
        foreach ($models as $model) {
            $topics = ArrayHelper::getColumn(PracticeTopics::find()->where(['practice_id' => $model['homework_id']])->all(), 'topic_id');

            $final = array_merge($model, ['topics' => SubjectTopics::find()->where(['id' => $topics])->asArray()->all()]);
        }

        return (new ApiResponse)->success($final, ApiResponse::SUCCESSFUL, 'Practice found');
    }

    /**
     * This returns videos not completely watched by student in a class
     * @return ApiResponse
     */
    public function actionIncompleteVideos()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $model = FileLog::findAll([
            'user_id' => Yii::$app->user->id,
            'is_completed' => SharedConstant::VALUE_ZERO
        ]);

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Videos not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Videos found');
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
            ->innerJoin('homeworks', "homeworks.id = practice_material.practice_id")//removed
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
    public function actionPracticeRecommendations()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }


        $class_id = Utility::getStudentClass(SharedConstant::VALUE_ONE);
        $student_id = Yii::$app->user->id;
        $models = QuizSummary::find()
            ->select('subject_id')
            ->where(['student_id' => $student_id])
            ->andWhere(['<>', 'type', 'recommendation'])
            ->groupBy('subject_id')
            ->asArray()
            ->all();

        $finalResult = [];
        foreach ($models as $model) {
            $topics = QuizSummaryDetails::find()
                ->select('quiz_summary_details.topic_id')
                ->innerJoin('quiz_summary', "quiz_summary.id = quiz_summary_details.quiz_id AND quiz_summary.type != 'recommendation'")
                ->innerJoin('subject_topics st', "st.id = quiz_summary_details.topic_id")
                ->where(['quiz_summary.subject_id' => $model['subject_id'], 'quiz_summary_details.student_id' => Yii::$app->user->id])
                ->groupBy('quiz_summary_details.topic_id')
                ->all();

            $subject = Subjects::find()->select(['id', 'slug', 'name', 'status', Yii::$app->params['subjectImage']])->where(['id' => $model['subject_id']])->asArray()->one();

            $topicOrders = [];
            foreach ($topics as $index => $topic) {
                $topicModels = QuizSummaryDetails::find()
                    ->alias('qsd')
                    ->select([
                        new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                        'COUNT(qsd.id) as total',
                        'SUM(case when qsd.selected = qsd.answer then 1 else 0 end) as correct',
                        'st.topic',
                        'st.id',
                        'st.subject_id',
                        Yii::$app->params['topicImage']
                    ])
                    ->innerJoin('subject_topics st', "st.id = qsd.topic_id AND st.subject_id = {$model['subject_id']} AND st.class_id = $class_id")
                    ->innerJoin('questions q', 'q.topic_id = qsd.topic_id')
                    ->where(['topic_id' => $topic->topic_id, 'student_id' => Yii::$app->user->id, 'st.subject_id' => $model['subject_id']])
                    //->where(['st.subject_id' => $model['subject_id']])
                    ->orderBy('score')
                    ->asArray()
                    ->groupBy('qsd.topic_id')
                    ->limit(10)
                    ->all();

                $topicOrders = [];
                foreach ($topicModels as $key => $inner) {
                    if ($key <= 3) {
                        $topicOrders[] = ['type' => 'single', 'topic' => $inner];
                    }

                    if ($key > 3 && $key <= 6) {
                        if (isset($topicModels[4])) {
                            $temp = array_splice($topicModels, 4, 3);
                            if (count($temp) == 1)
                                $topicOrders[] = ['type' => 'single', 'topic' => $inner];
                            else
                                $topicOrders[] = ['type' => 'mix', 'topic' => $temp];
                        }
                    }

                    if ($key > 6 && $key <= 9) {
                        if (isset($topicModels[7])) {
                            $temp = array_splice($topicModels, 8, 3);
                            if (count($temp) == 1)
                                $topicOrders[] = ['type' => 'single', 'topic' => $inner];
                            else
                                $topicOrders[] = ['type' => 'mix', 'topic' => $temp];
                        }
                    }
                }

            }


            $tutor_sessions = TutorSession::find()
                ->select([
                    'tutor_session.*',
                    new Expression("'live_class' as type"),
                ])
                ->where([
                    'student_id' => $student_id,
                    'subject_id' => $model['subject_id'],
                    'meta' => SharedConstant::RECOMMENDATION,
                    'status' => SharedConstant::PENDING_STATUS,
                    'is_school' => 1
                ])
                //Student only sees live_class/remedial that supposed to hold within the next 72hours
                ->andWhere('availability > DATE_SUB(NOW(), INTERVAL 72 HOUR)')
                ->asArray()->all();


            $recommendedVideos = $this->getRecommendedVideos($student_id, $model);
            $practices = $this->getRecommendedPractices($student_id, $model);

            $topicOrders = array_merge($topicOrders, $recommendedVideos, $tutor_sessions, $practices);

            $finalResult[] = array_merge(
                $subject,
                [
                    'topics' => $topicOrders,
                ]);
        }


        if (!$finalResult) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice Topics not found');
        }

        return (new ApiResponse)->success($finalResult, ApiResponse::SUCCESSFUL, 'Practice Topics found');
    }

    protected function getRecommendedVideos($student_id, $model)
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
                'receiver_id' => Yii::$app->user->id,
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
            ->asArray()->all();

        return $recommendedVideos;
    }

    protected function getRecommendedPractices($student_id, $model)
    {
        $practicesList = Homeworks::find()
            ->select([
                'homeworks.*',
                new Expression("'practice' as type"),
            ])
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
                ")
//                ->andWhere([
//                    'NOT IN',
//                    'id',
//                    ArrayHelper::getColumn(
//                        QuizSummary::find()
//                            ->where([
//                                'subject_id' => $model['subject_id'],
//                                'student_id' => Yii::$app->user->id
//                            ])
//                            ->all(),
//                        'homework_id'
//                    )
//                ])
            ->all();

        $practices = [];
        foreach ($practicesList as $practice) {
            $practices[] = [
                'type' => 'practice',
                'practice_id' => $practice->id,
                'topics' => $practice->topics,
            ];
        }
        return $practices;
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

        if (!$homework_model = $model->initializePracticeTemp()) {
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

        // Initialize the practice
        $startPractice = new StartPracticeForm();
        $startPractice->type = 'mix';
        $startPractice->topic_ids = $topicIDs;
        $startPractice->practice_type = 'diagnostic';
        if (!$homework_model = $startPractice->initializePracticeTemp()) {
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (Homeworks::find()
            ->alias('hm')
            ->where(['hm.student_id' => $student_id, 'hm.id' => $practice_id, 'qs.student_id' => $student_id])
            ->innerJoin('quiz_summary qs', 'qs.homework_id = hm.id AND qs.submit=1')
            ->exists()) {
            $form->addError('practice_id', 'Practice already taken');
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!Homeworks::find()
            ->alias('hm')
            ->where(['hm.student_id' => $student_id, 'hm.id' => $practice_id])
            ->innerJoin('practice_topics pt', 'pt.practice_id = hm.id')
            ->exists()) {
            $form->addError('practice_id', 'Topics not available, try again.');
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $homework = Homeworks::findOne([['student_id' => $student_id, 'id' => $practice_id]]);

        $model = new StartQuizSummaryForm;
        if (!$practice_model = $model->getTotalQuestions($homework)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice Temp not started!');
        }

        return (new ApiResponse)->success($practice_model, ApiResponse::SUCCESSFUL, 'Practice Temp started!');
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
    }


    /**
     * This process diagnostic and practice attempts
     * @return ApiResponse
     */
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
            $quizSummary->teacher_id = $homework->teacher_id;
            $quizSummary->student_id = \Yii::$app->user->id;
            $quizSummary->class_id = Utility::getStudentClass();
            $quizSummary->subject_id = $homework->subject_id;
            $quizSummary->term = Utility::getStudentTermWeek('term');
            $quizSummary->total_questions = count($attempts);
            $quizSummary->type = $homework->type;
            if (!$quizSummary->validate()) {
                return (new ApiResponse)->error($quizSummary->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice not validated');
            }


            if (!$quizSummary->save()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save your attempt');
            }

            foreach ($attempts as $question) {
                if (!isset($question['selected']) || !isset($question['question']) || !isset($question['time_spent']))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Attempt data is not valid');


                if (!in_array($question['selected'], SharedConstant::QUESTION_ACCEPTED_OPTIONS))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, "Invalid option '{$question['selected']}' provided");

                $qsd = new QuizSummaryDetails();
                $qsd->quiz_id = $quizSummary->id;
                $qsd->question_id = $question['question'];
                $qsd->selected = $question['selected'];
                $questionModel = Questions::findOne(['id' => $question['question']]);
                $qsd->answer = $questionModel->answer;
                $qsd->topic_id = $questionModel->topic_id;
                $qsd->student_id = \Yii::$app->user->id;
                $qsd->homework_id = $quizSummary->homework_id;
                $qsd->time_spent = $question['time_spent'];

                if ($question['selected'] != $questionModel->answer)
                    $failedCount = +1;

                if ($question['selected'] == $questionModel->answer)
                    $correctCount = +1;

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

            $dbtransaction->commit();
            return (new ApiResponse)->success($quizSummary, ApiResponse::SUCCESSFUL, 'Practice processing completed');
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Attempt was not successfully processed');
        }

    }
}