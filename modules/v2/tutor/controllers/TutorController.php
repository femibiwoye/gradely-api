<?php

namespace app\modules\v2\tutor\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\models\{ApiResponse, TutorProfile, TutorSession, Review, SubjectTopics};

class TutorController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\UserModel';

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
            'class' => HttpBearerAuth::className(),
            'except' => ['index', 'profile'],

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

    public function actionIndex()
    {
        $s = Yii::$app->request->get('s');
        $subject = Yii::$app->request->get('subject');
        $curriculum = Yii::$app->request->get('curriculum');
        $gender = Yii::$app->request->get('gender');
        $day = Yii::$app->request->get('day');
        $sort = Yii::$app->request->get('sort');
        $models = TutorProfile::find();
        $models = $models->where(['availability' => SharedConstant::VALUE_ONE])
            ->leftJoin('user', 'user.id = tutor_profile.tutor_id')
            ->leftJoin('user_profile', 'user_profile.user_id = tutor_profile.tutor_id')
            ->leftJoin('tutor_subject', 'tutor_subject.tutor_id = tutor_profile.tutor_id')
            ->leftJoin('subjects', 'subjects.id = tutor_subject.subject_id')
            ->leftJoin('exam_type', 'exam_type.id = tutor_subject.curriculum_id')
            ->leftJoin('tutor_availability', 'tutor_availability.user_id = tutor_profile.tutor_id')
            ->andFilterWhere(['OR', ['like', 'user.lastname', '%' . $s . '%', false],
                ['like', 'user.firstname', '%' . $s . '%', false],
                ['like', 'user_profile.about', '%' . $s . '%', false]])
            ->andFilterWhere(['subjects.slug' => $subject])
            ->andFilterWhere(['exam_type.slug' => $curriculum])
            ->andFilterWhere(['user_profile.gender' => $gender])
            ->andFilterWhere(['tutor_availability.day' => $day]);

        if ($sort) {
            if ($sort == 'a-z') {
                $models = $models->orderBy(['user.firstname' => SORT_ASC]);
            } elseif ($sort == 'z-a') {
                $models = $models->orderBy(['user.firstname' => SORT_DESC]);
            } elseif ($sort == 'low-price') {
                $models = $models->orderBy(['price' => SORT_ASC]);
            } elseif ($sort == 'high-price') {
                $models = $models->orderBy(['price' => SORT_DESC]);
            }
        }

        $models = $models->groupBy('tutor_profile.tutor_id');
        if (!$models->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $models,
            'pagination' => [
                'pageSize' => 2,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' tutors found fit your search', $provider);
    }

    public function actionProfile($tutor_id)
    {
        $model = TutorProfile::find()
            ->with(['calendar'])
            ->where(['tutor_id' => $tutor_id])->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Tutor record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Tutor record found');
    }

    public function actionTutorReview()
    {
        $review_model = Review::findOne(['sender_id' => Yii::$app->user->id, 'receiver_id' => Yii::$app->request->post('receiver_id')]);
        if ($review_model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Review already made');
        }

        $type = Yii::$app->user->identity->type;
        if ($type == SharedConstant::TYPE_SCHOOL || $type == SharedConstant::TYPE_TEACHER) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission failed');
        }

        $session_id = Yii::$app->request->post('session_id');
        $rate = Yii::$app->request->post('rate');
        $sender_id = Yii::$app->user->id;
        $receiver_id = Yii::$app->request->post('receiver_id');
        $form = new \yii\base\DynamicModel(compact('session_id', 'sender_id', 'rate', 'receiver_id'));
        $form->addRule(['session_id', 'sender_id', 'receiver_id', 'rate'], 'required');
        $form->addRule(['session_id', 'rate', 'sender_id', 'receiver_id'], 'integer');
        $form->addRule(['rate'], 'compare', ['operator' => '<=', 'compareValue' => 5]);
        $form->addRule('session_id', 'exist', ['targetClass' => TutorSession::className(), 'targetAttribute' => ['session_id' => 'id']]);
        $form->addRule(['sender_id', 'receiver_id'], 'exist', ['targetClass' => $this->modelClass::className(), 'targetAttribute' => ['sender_id' => 'id']]);
        $form->addRule(['receiver_id'], 'exist', ['targetClass' => $this->modelClass::className(), 'targetAttribute' => ['receiver_id' => 'id']]);
        $form->addRule(['sender_id'], 'exist', ['targetClass' => TutorSession::className(), 'targetAttribute' => ['sender_id' => 'student_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = new Review;
        $model->attributes = Yii::$app->request->post();
        $model->sender_id = $sender_id;
        if (!$model->save()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Review not made');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Review made');
    }

    public function actionTutorRateStudent()
    {
        $type = Yii::$app->user->identity->type;
        if ($type == SharedConstant::TYPE_SCHOOL || $type == SharedConstant::TYPE_TEACHER || $type == SharedConstant::TYPE_STUDENT || $type == SharedConstant::TYPE_PARENT) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission failed');
        }

        $session_id = Yii::$app->request->post('session_id');
        $sender_id = Yii::$app->user->identity->id;
        $receiver_id = Yii::$app->request->post('receiver_id');
        $topic_taught = Yii::$app->request->post('topic_taught');
        $recommended_topic = Yii::$app->request->post('recommended_topic');
        $tutor_rate_student = Yii::$app->request->post('tutor_rate_student');
        $tutor_comment = Yii::$app->request->post('tutor_comment');

        $form = new \yii\base\DynamicModel(compact('session_id', 'sender_id', 'receiver_id', 'recommended_topic', 'tutor_rate_student', 'tutor_comment', 'topic_taught'));
        $form->addRule(['session_id', 'sender_id', 'receiver_id'], 'required');
        $form->addRule(['session_id', 'sender_id', 'receiver_id'], 'integer');
        $form->addRule(['topic_taught', 'recommended_topic', 'tutor_comment', 'tutor_rate_student'], 'string');
        $form->addRule('session_id', 'exist', ['targetClass' => TutorSession::className(), 'targetAttribute' => ['session_id' => 'id']]);
        $form->addRule(['sender_id'], 'exist', ['targetClass' => $this->modelClass::className(), 'targetAttribute' => ['sender_id' => 'id']]);
        $form->addRule(['receiver_id'], 'exist', ['targetClass' => $this->modelClass::className(), 'targetAttribute' => ['receiver_id' => 'id']]);
        $form->addRule(['topic_taught'], 'exist', ['targetClass' => SubjectTopics::className(), 'targetAttribute' => ['topic_taught' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = new Review;
        $model->attributes = Yii::$app->request->post();
        $model->sender_id = $sender_id;
        if (!$model->save()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Review not made');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Review made');
    }
}

