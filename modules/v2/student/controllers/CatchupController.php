<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

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
    Subjects};
use app\modules\v2\student\models\{StartPracticeForm, StartQuizSummaryForm};
use app\modules\v2\components\{SharedConstant, Utility};


/**
 * Schools/Parent controller
 */
class CatchupController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Catchup';
    private $subject_array = array();
    private $single_topic_array = array('type' => SharedConstant::SINGLE_TYPE_ARRAY);
    private $mix_topic_array = array();

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

    public function actionWatchVideo($video_id)
    {

        $form = new \yii\base\DynamicModel(compact('video_id'));
        $form->addRule(['video_id'], 'exist', ['targetClass' => VideoAssign::className(), 'targetAttribute' => ['video_id' => 'content_id']]);
        $form->addRule(['video_id'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $video = VideoContent::findOne(['id' => $video_id]);

        $videos = ['https://www.youtube.com/embed/LRhOuyXemwI',
            'https://www.youtube.com/embed/UZByHx5fHzA',
            'https://www.youtube.com/embed/YXWFUJj7Ac',
            'http://www.html5rocks.com/en/tutorials/video/basics/devstories.mp4'];

        $video = array_merge(
            ArrayHelper::toArray($video),
            [
                'url'=>$videos[mt_rand(0,3)],
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
}