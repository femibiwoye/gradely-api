<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\components\Utility;
use Yii;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\models\{feed, ApiResponse, Homeworks, TutorSession, FeedComment, FeedLike};
use app\modules\v2\components\SharedConstant;

class FeedController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Feed';

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
            'class' => CompositeAuth::className(),
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
    }

    public function actionUpcoming()
    {
        $new_announcements = array_merge((new Homeworks)->getnewHomeworks(), (new TutorSession)->getNewSessions());
        if (!$new_announcements) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Annoucements not found!');
        }

        array_multisort(array_column($new_announcements, 'date_time'), $new_announcements);
        return (new ApiResponse)->success($new_announcements, ApiResponse::SUCCESSFUL, 'Annoucements found');
    }

    public function actionFeedComment($post_id)
    {
        $model = new FeedComment;
        $model->user_id = Yii::$app->user->id;
        $model->comment = Yii::$app->request->post('comment');
        $model->feed_id = $post_id;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Comment not added');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Comment added');
    }

    public function actionFeedLike($post_id)
    {
        $model = Feed::findOne(['id' => $post_id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Post is not found!');
        }

        if ($model->feedLike) {
            if (!$model->feedDisliked()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Post is not disliked!');
            }

            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Post is disliked');
        }

        $model = new FeedLike;
        $model->parent_id = $post_id;
        $model->user_id = Yii::$app->user->id;
        $model->type = SharedConstant::FEED_TYPE;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Post not liked');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Post liked');
    }

    public function actionCommentLike($comment_id)
    {
        $model = FeedComment::findOne(['id' => $comment_id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Comment is not found!');
        }

        if ($model->feedCommentLike) {
            if (!$model->feedCommentDisliked()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Comment is not disliked!');
            }

            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Comment is disliked');
        }

        $model = new FeedLike;
        $model->parent_id = $comment_id;
        $model->user_id = Yii::$app->user->id;
        $model->type = SharedConstant::COMMENT_TYPE;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Comment not liked');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Comment liked');
    }

    public function actionCreate()
    {
        $model = new Feed;
        $model->attributes = Yii::$app->request->post();
        $model->user_id = Yii::$app->user->id;

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Announcement not made');
        }

        return (new ApiResponse)->success(ArrayHelper::toArray($model), ApiResponse::SUCCESSFUL, 'Announcement made successfully');
    }

    public function actionIndex()
    {
        $models = $this->modelClass::find()->where(['OR',
            ['user_id' => Yii::$app->user->id],
            ['class_id' => Utility::getTeacherClassesID(Yii::$app->user->id)]
        ])->all();
        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Feeds not found');
        }

        $provider = new \yii\data\ArrayDataProvider([
            'allModels' => $models,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Feeds found');
    }
}
