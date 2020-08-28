<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\models\FileLog;
use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use app\modules\v2\models\{Homeworks, ApiResponse, FeedComment, PracticeMaterial};
use app\modules\v2\components\SharedConstant;


/**
 * Schools/Parent controller
 */
class CatchupController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\QuizSummary';

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

        $file_log_id = FileLog::findOne(['is_completed' => SharedConstant::VALUE_ONE, 'id' => $id]);

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

        return (new ApiResponse)->success($file_log, ApiResponse::SUCCESSFUL, 'Videos Fount');

    }
}