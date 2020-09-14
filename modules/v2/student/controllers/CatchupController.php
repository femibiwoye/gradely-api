<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\models\Feed;
use app\modules\v2\models\FeedLike;
use app\modules\v2\models\FileLog;
use app\modules\v2\models\PracticeTopics;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\VideoAssign;
use app\modules\v2\models\VideoContent;
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
    User
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
    private $topics;
    private $weekly_recommended_topics = array();
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

    public function actionRecentPractice()
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_STUDENT) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission failed');
        }

        $models = Homeworks::find()
            ->innerJoin('quiz_summary', 'quiz_summary.homework_id = homeworks.id')
            // ->where(['homeworks.student_id' => Yii::$app->user->id, 'quiz_summary.submit' => SharedConstant::VALUE_ONE]) //to be returned
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

    public function actionVideoComments($id)
    {
        $model = FeedComment::find()
            //->where(['feed_id' => $id, 'type' => SharedConstant::TYPE_VIDEO, 'user_id' => Yii::$app->user->id]) //to be returned
            ->limit(10)
            ->orderBy('id DESC')
            ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionCommentVideo()
    {
        $video_id = Yii::$app->request->post('video_id');
        $comment = Yii::$app->request->post('comment');
        $type = 'video';
        $form = new \yii\base\DynamicModel(compact('video_id', 'comment', 'type'));
        $form->addRule(['video_id', 'comment'], 'required');
        $form->addRule(['video_id'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        //$material = PracticeMaterial::findOne(['id' => $video_id]);

        $model = new FeedComment;
        $model->attributes = Yii::$app->request->post();
        $model->user_id = Yii::$app->user->id;
        $model->feed_id = $video_id;
        $model->type = $type;
        if (!$model->save(false)) {
            return (new ApiResponse)->error($model->errors, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record saved');
    }

    public function actionVideoLikes($video_id)
    {

        $status = Yii::$app->request->post('status');

        $form = new \yii\base\DynamicModel(compact('video_id', 'status'));
        $form->addRule(['video_id', 'status'], 'required');
        $form->addRule(['status'], 'in', ['range' => [1, 0]]);
        $form->addRule(['video_id'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = VideoContent::findOne(['id' => $video_id, 'content_type' => 'video']);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video is not found!');
        }

        $likeStatus = FeedLike::find()->where(['parent_id' => $video_id, 'user_id' => Yii::$app->user->id, 'type' => 'video']);
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
        $model->parent_id = $video_id;
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

    public function actionClassResources($class_id)
    {
        $model = PracticeMaterial::find()
            ->innerJoin('feed', 'feed.id = practice_material.practice_id')
            // ->where(['feed.class_id' => $class_id]) //to be returned
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

    public function actionWatchVideoAgain($id)
    {

        $file_log_id = FileLog::find()
            ->innerJoin('video_content', 'video_content.id = file_log.file_id')
//            ->andWhere([
//                'is_completed' => SharedConstant::VALUE_ONE,
//                'id' => $id,
//                'user_id' => Yii::$app->user->id
//            ]) //to be returned
            ->limit(6)
            ->one();

        if (!$file_log_id) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($file_log_id, ApiResponse::SUCCESSFUL, 'Watch Video Again');

    }

    public function actionVideosWatched()
    {

        $class_id = Utility::getStudentClass();
        $file_log = FileLog::find()
//            ->where([
//                'is_completed' => SharedConstant::VALUE_ONE,
//                'user_id' => Yii::$app->user->id,
//                'type' => SharedConstant::TYPE_VIDEO,
//                'class_id'=>$class_id
//            ]) //to be returned
            ->groupBy('file_id')
            ->limit(6)
            ->orderBy('id DESC')
            ->all();

        if (!$file_log) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Records not found');
        }

        return (new ApiResponse)->success($file_log, ApiResponse::SUCCESSFUL, 'Videos Found');

    }

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

        $videos = ['https://www.youtube.com/embed/LRhOuyXemwI',
            'https://www.youtube.com/embed/UZByHx5fHzA',
            'https://www.youtube.com/embed/YXWFUJj7Ac',
            'http://www.html5rocks.com/en/tutorials/video/basics/devstories.mp4'];

        $video = array_merge(
            ArrayHelper::toArray($video),
            [
                'url' => $videos[mt_rand(0, 3)],
                'assign' => $video->videoAssigned,
                'topic' => $video->videoAssigned->topic,
                'subject' => $video->videoAssigned->topic->subject
            ]);


        if (!$video) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video not found');
        }

        return (new ApiResponse)->success($video, ApiResponse::SUCCESSFUL, 'Video Found');

    }

    public function actionUpdateVideoCompleted($video_id)
    {
        $class_id = Utility::getStudentClass();;

        $duration = Yii::$app->request->post('duration');

        $form = new \yii\base\DynamicModel(compact('video_id', 'class_id', 'duration'));
        $form->addRule(['video_id', 'duration'], 'required');
        $form->addRule(['video_id'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_id' => 'id']]);

        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');

        $file_log = FileLog::findOne([
            'is_completed' => SharedConstant::VALUE_ZERO,
            'user_id' => Yii::$app->user->id,
            'type' => SharedConstant::TYPE_VIDEO,
            'file_id' => $video_id,
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

    public function actionUpdateVideoLength($video_id)
    {
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

    public function actionDiagnostic()
    {
        $class_id = Utility::getStudentClass(SharedConstant::VALUE_ONE);
        if (!$class_id) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found');
        }

        $model = Subjects::find()
            ->leftJoin('quiz_summary qs', 'qs.subject_id = subjects.id AND qs.submit = 1')
            ->where(['status' => 1, 'school_id' => null, 'category' => ['all', Utility::getStudentClassCategory($class_id)]])
            ->andWhere(['is', 'qs.subject_id', null])
            ->groupBy('id')
            ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subject not found');
        }


        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, count($model) . ' subjects found');
    }

    public function actionRecentPractices()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $models = QuizSummary::find()
            //->where(['student_id' => Yii::$app->user->id, 'submit' => SharedConstant::VALUE_ONE]) //to be returned
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

    public function actionIncompleteVideos()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $model = FileLog::findAll([
            //'user_id' => Yii::$app->user->id, //to be returned
            'is_completed' => SharedConstant::VALUE_ZERO
        ]);

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Videos not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Videos found');
    }

    public function actionClassMaterials()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }
        $class_id = Utility::getStudentClass();

        $model = PracticeMaterial::find()
            ->innerJoin('homeworks', "homeworks.id = practice_material.practice_id")//removed
            //->innerJoin('homeworks',"homeworks.id = practice_material.practice_id AND homeworks.class_id = $class_id") to be returned
            //->where(['user_id' => Yii::$app->user->identity->id]) //remogit pullved
            // ->where(['class_id'=>Utility::getStudentClass()]) //to be returned
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

    public function actionPracticeTopics()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }


        $class_id = Utility::getStudentClass(SharedConstant::VALUE_ONE);

        $models = QuizSummary::find()
            ->select('subject_id')
            ->where(['student_id' => Yii::$app->user->id])
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

            $subject = Subjects::find()->select(['id', 'slug', 'name', 'status'])->where(['id' => $model['subject_id']])->asArray()->one();

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
                        'st.image'
                    ])
                    ->innerJoin('subject_topics st', "st.id = qsd.topic_id AND st.subject_id = {$model['subject_id']} AND st.class_id = $class_id")
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

            $finalResult[] = array_merge($subject, ['topics' => $topicOrders]);
        }


        if (!$finalResult) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Practice Topics not found');
        }

        return (new ApiResponse)->success($finalResult, ApiResponse::SUCCESSFUL, 'Practice Topics found');
    }

    public function actionInitializePractice()
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

    public function actionHomeworkRecommendation($quiz_id)
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $quizSummary = QuizSummary::find()->where([
            'id' => $quiz_id, 'submit' => 1,
            'student_id' => Yii::$app->user->id
        ])->one();
        if (!$quizSummary)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not found');


        //$topics retrieves low scoring topic_ids
        $topics = QuizSummaryDetails::find()
            ->alias('qsd')
            ->select([
                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as score'),
                'qsd.topic_id',
            ])
            ->where([
                'qsd.student_id' => Yii::$app->user->id,
                'homework_id' => $quizSummary->homework_id
            ])
            ->orderBy(['score' => SORT_ASC])
            ->asArray()
            ->limit(SharedConstant::VALUE_TWO)
            ->groupBy('qsd.topic_id')
            ->all();

        //$topic_objects retrieves topic objects
        $topic_objects = SubjectTopics::find()
            ->select([
                'subject_topics.*',
                new Expression("'practice' as type")
            ])
            ->where(['id' => ArrayHelper::getColumn($topics, 'topic_id')])
            ->asArray()
            ->all();

        //retrieves assign videos to the topic
        $video = VideoContent::find()
            ->select([
                'video_content.*',
                new Expression("'video' as type")
            ])
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->where(['video_assign.topic_id' => ArrayHelper::getColumn($topics, 'topic_id')])
            ->limit(SharedConstant::VALUE_ONE)
            ->asArray()
            ->all();

        if (!$topic_objects) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework recommendations not found');
        }


        $topics = array_merge($topic_objects, $video);

        return (new ApiResponse)->success($topics, ApiResponse::SUCCESSFUL, 'Homework recommendations found');

    }

    public function actionGenerateWeeklyRecommendation()
    {
        if (date('l') != SharedConstant::CURRENT_DAY) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'The weekly Recommendation cannot be generated on a ' . date('l'));
        }

        //student_recommendations depicts the students that has received the weekly recommendation
        $student_recommendations = ArrayHelper::getColumn(
            Recommendations::find()
                ->where([
                    'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ZERO],
                    'DATE(created_at)' => date('Y-m-d')
                ])
                ->andWhere('WEEK(CURDATE()) = WEEK(created_at)')//checking on-going week
                ->all(),
            'student_id'
        );

        //student_ids depicts the list of students
        $student_ids = ArrayHelper::getColumn(
            User::find()->where(['type' => SharedConstant::TYPE_STUDENT])->andWhere(['<>', 'status', SharedConstant::VALUE_ZERO])->andWhere(['NOT IN', 'id', $student_recommendations])->all(),
            'id'
        );


        if (empty($student_ids)) {
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Weekly recommendations are already generated');
        }


        foreach ($student_ids as $student) {
            $this->weeklyRecommendation($student);
        }

        if (empty($this->topics)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Weekly recommendations not found');
        }

        return (new ApiResponse)->success($this->topics, ApiResponse::SUCCESSFUL, 'Weekly recommendations found');
    }

    public function weeklyRecommendation($student)
    {
        $school_id = StudentSchool::find()
            ->select(['school_id', 'class_id'])
            ->where(['student_id' => $student])
            ->asArray()
            ->one();

        if (!$school_id) {
            $term = SessionTermOnly::widget(['nonSchool' => true]);
            $week = SessionTermOnly::widget(['nonSchool' => true, 'weekOnly' => true]);
        } else {
            $term = SessionTermOnly::widget(['id' => $school_id['school_id']]);
            $week = SessionTermOnly::widget(['id' => $school_id['school_id'], 'weekOnly' => true]);
        }

        $this->subjects = ArrayHelper::getColumn(QuizSummary::find()
            ->select('subject_id')
            ->where(['student_id' => $student])
            ->groupBy('subject_id')
            ->asArray()
            ->all(),
            'subject_id'
        );

        //array_push($this->subjects, SharedConstant::VALUE_THREE);

        $this->previousWeekRecommendedSubjects(); //filters out the previous week subjects.
        foreach ($this->subjects as $subject) {

            $model = SubjectTopics::find()
                ->select([
                    'subject_topics.id',
                    'subject_topics.topic',
                    'subject_topics.week_number',
                    'subject_topics.term',
                    'subjects.name as subject_name',
                    'subjects.id as subject_id',
                    new Expression("'practice' AS type"),
                ])
                ->innerJoin('subjects', 'subjects.id = subject_topics.subject_id')
                ->where([
                    'subject_topics.subject_id' => $subject,
                    'subject_topics.term' => $term,
                    'subject_topics.class_id' => $school_id['class_id']
                ])
                ->orWhere(['=', 'subject_topics.week_number', $week])
                ->orWhere(['<', 'subject_topics.week_number', $week])
                ->orWhere(['>', 'subject_topics.week_number', $week])
                ->asArray()
                ->limit(SharedConstant::VALUE_ONE)
                ->all();

            $this->weekly_recommended_topics = array_merge($this->weekly_recommended_topics, $model);
            if (sizeof($this->weekly_recommended_topics) == SharedConstant::VALUE_THREE) {
                break;
            }
        }

        if (!$this->weekly_recommended_topics) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Weekly recommendations not found');
        }

        $weekly_recommended_videos = VideoContent::find()
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->where([
                'video_assign.topic_id' => ArrayHelper::getColumn(
                    $this->weekly_recommended_topics, 'id')
            ])
            ->limit(SharedConstant::VALUE_TWO)
            ->all();

        $this->topics = array_merge($this->weekly_recommended_topics, $weekly_recommended_videos);

        $this->createRecommendations($this->topics, $student);
    }

    private function previousWeekRecommendedSubjects()
    {
        $previous_week_recommendations = ArrayHelper::getColumn(RecommendationTopics::find()
            ->select('subject_id')
            ->where('WEEK(CURDATE()) = WEEK(created_at) - 1')
            ->groupBy('subject_id')
            ->asArray()
            ->all(),
            'subject_id'
        );

        if (!empty($previous_week_recommendations)) {
            $this->subjects = array_diff($this->subjects, $previous_week_recommendations);
            if (count($this->subjects) == SharedConstant::VALUE_ONE) {
                $keys = array_rand($previous_week_recommendations, SharedConstant::VALUE_TWO); //select random keys from the previous week recommendations
                $this->subjects = array_merge($this->subjects, $previous_week_recommendations[$keys[SharedConstant::VALUE_ZERO]], $previous_week_recommendations[$keys[SharedConstant::VALUE_ONE]]);
            } elseif (empty($this->subjects)) {
                $keys = array_rand($previous_week_recommendations, SharedConstant::VALUE_THREE); //select random keys from the previous week recommendations
                $this->subjects = array_merge($this->subjects, $previous_week_recommendations[$keys[SharedConstant::VALUE_ZERO]], $previous_week_recommendations[$keys[SharedConstant::VALUE_ONE]], $previous_week_recommendations[$keys[SharedConstant::VALUE_TWO]]);
            }
        }
    }

    private function createRecommendations($recommendations, $student)
    {
        if (!empty($recommendations)) {
            $dbtransaction = Yii::$app->db->beginTransaction();
            try {
                $model = new Recommendations;
                $model->student_id = $student;
                $model->category = SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ZERO];
                if (!$model->save()) {
                    return false;
                }

                if (!$this->createRecommendedTopics($recommendations, $model)) {
                    return false;
                }

                $dbtransaction->commit();
            } catch (Exception $e) {
                $dbtransaction->rollBack();
                return false;
            }

            return true;
        }
    }

    private function createRecommendedTopics($objects, $recommendation)
    {
        foreach ($objects as $object) {
            $model = new RecommendationTopics;
            $model->recommendation_id = $recommendation->id;
            $model->subject_id = $object['subject_id'];
            $model->student_id = $recommendation->student_id;
            $model->object_id = $object['id'];
            $model->object_type = $object['type'] ? $object['type'] : 'video';
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }

    public function actionWeeklyRecommendations()
    {
        $recommendations = Recommendations::find()
            ->where([
                'student_id' => Yii::$app->user->id,
                'category' => SharedConstant::RECOMMENDATION_TYPE[SharedConstant::VALUE_ZERO],
            ])
            ->andWhere(['=', new Expression('DAYOFWEEK(created_at)'), SharedConstant::VALUE_ONE])
            ->all();

        if (!$recommendations) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Weekly recommendations not found');
        }

        return (new ApiResponse)->success($recommendations, ApiResponse::SUCCESSFUL, 'Weekly recommendations found');
    }
}