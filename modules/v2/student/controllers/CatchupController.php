<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\models\FileLog;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\VideoContent;
use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use app\modules\v2\models\{Homeworks, ApiResponse, FeedComment, PracticeMaterial, Catchup, SubjectTopics, QuizSummary};
use app\modules\v2\components\{SharedConstant, Utility};


/**
 * Schools/Parent controller
 */
class CatchupController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Catchup';

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
                    ->where(['homeworks.student_id' => Yii::$app->user->identity->id, 'quiz_summary.submit' => SharedConstant::VALUE_ONE])
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
                        ->where(['feed_id' => $id, 'type' => SharedConstant::TYPE_VIDEO, 'user_id' => Yii::$app->user->identity->id])
                        ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionCommentVideo()
    {
        $practice_id = Yii::$app->request->post('practice_id');
        $comment = Yii::$app->request->post('comment');
        $form = new \yii\base\DynamicModel(compact('practice_id', 'comment'));
        $form->addRule(['practice_id', 'comment'], 'required');
        $form->addRule(['practice_id'], 'exist', ['targetClass' => PracticeMaterial::className(), 'targetAttribute' => ['practice_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        } 

        $model = new FeedComment;
        $model->attributes = Yii::$app->request->post();
        $model->user_id = Yii::$app->user->identity->id;
        $model->feed_id = $practice_id;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record saved');
    }

    public function actionVideo($id)
    {
        $model = PracticeMaterial::find()
                    ->where(['id' => $id, 'filetype' => SharedConstant::TYPE_VIDEO, 'user_id' => Yii::$app->user->identity->id])
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
                    ->where(['feed.class_id' => $class_id, 'feed.user_id' => Yii::$app->user->identity->id])
                    ->orderBy(['feed.update_at' => SORT_DESC]);

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => 6,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found');

    }
    
    public function actionWatchVideoAgain($id){

        $file_log_id = FileLog::find()
                       ->innerJoin('video_content', 'video_content.file_id = file_log.id')
                       ->andWhere([
                           'is_completed' => SharedConstant::VALUE_ONE,
                           'id' => $id,
                           'user_id' => Yii::$app->user->id
                       ])
                       ->one();

        if(!$file_log_id){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
    }

    return (new ApiResponse)->success($file_log_id, ApiResponse::SUCCESSFUL, 'Watch Video Again');

    }

    public function actionVideosWatched(){

        $file_log = FileLog::findAll([
            'is_completed' => SharedConstant::VALUE_ONE,
            'user_id' => Yii::$app->user->id,
            'type' => SharedConstant::TYPE_VIDEO,
        ]);

        if(!$file_log){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Records not found');
        }

        return (new ApiResponse)->success($file_log, ApiResponse::SUCCESSFUL, 'Videos Found');

    }

    public function actionUpdateVideoCompleted(){

        $student_id = Yii::$app->user->id;

        $student_class = StudentSchool::findOne(['student_id' => $student_id]);
        $class_id = $student_class->class_id;

        $video_id = Yii::$app->request->post('video_id');

        $form = new \yii\base\DynamicModel(compact('video_id', 'class_id'));
        $form->addRule(['video_id'], 'required');
        $form->addRule(['video_id'], 'exist', ['targetClass' => VideoContent::className(), 'targetAttribute' => ['video_id' => 'id']]);

        if (!$form->validate())
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');

        $file_log = FileLog::findOne([
            'user_id' => Yii::$app->user->id,
            'type' => SharedConstant::TYPE_VIDEO,
            'class_id' => $class_id,
            'file_id' => $video_id,
        ]);

        if(!$file_log)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');

        if($file_log->total_duration == $file_log->current_duration){

            $file_log->is_completed = SharedConstant::VALUE_ONE;
        }

        if(!$file_log->save()){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Records not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $file_log,
            'pagination' => [
                'pageSize' => 6,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider, ApiResponse::SUCCESSFUL, 'Videos Found');

    }

    public function actionUpdateVideoLength($id)
    {
        $current_duration = Yii::$app->request->post('current_duration');
        $model = FileLog::find()
                       ->andWhere([
                           'is_completed' => SharedConstant::VALUE_ONE,
                           'id' => $id,
                           'user_id' => Yii::$app->user->id
                       ])
                       ->one();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model->current_duration = $current_duration;
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Video duration not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Video duration updated');
    }

    public function actionDiagnostic()
    {
        $class_id = Utility::getStudentClass(SharedConstant::VALUE_ZERO);
        if (!$class_id) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found');
        }

        $model = SubjectTopics::find()
                    ->join('LEFT OUTER JOIN', 'quiz_summary', 'quiz_summary.subject_id = subject_topics.id')
                    ->where([
                        'quiz_summary.class_id' => $class_id,
                        'subject_topics.school_id' => SharedConstant::VALUE_NULL,
                        'subject_topics.status' => SharedConstant::VALUE_ONE,
                        'subject_topics.type' => SharedConstant::QUIZ_SUMMARY_TYPE[1]
                    ])
                    ->limit(6)
                    ->orderBy(['subject_topics.id' => SORT_ASC])
                    ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }


        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionRecentPractices()
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $model = QuizSummary::find()
                    ->where(['student_id' => Yii::$app->user->identity->id, 'submit' => SharedConstant::VALUE_ONE])
                    ->andWhere(['<>', 'type', SharedConstant::QUIZ_SUMMARY_TYPE[0]])
                    ->orderBy(['submit_at' => SORT_DESC])
                    ->limit(6)
                    ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }
}