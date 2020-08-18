<?php

namespace app\modules\v2\teacher\controllers;

use Yii;
use app\modules\v2\models\{Homeworks, ApiResponse, HomeworkQuestions, Questions};
use app\modules\v2\teacher\models\{HomeworkQuestionsForm};
use app\modules\v2\components\SharedConstant;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};

class QuestionController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Questions';

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

    public function actionQuestions()
    {
        $homework_id = Yii::$app->request->get('homework_id');
        $form = new \yii\base\DynamicModel(compact('homework_id'));
        $form->addRule(['homework_id'], 'required');
        $form->addRule(['homework_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = Questions::find()
                    ->innerJoin('homework_questions', 'homework_questions.question_id = questions.id')
                    ->where(['homework_questions.homework_id' => $homework_id])
                    ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionHomeworkQuestions($homework_id)
    {
        $form = new HomeworkQuestionsForm;
        $form->attributes = Yii::$app->request->post();
        $form->homework_id = $homework_id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record validaton failed');
        }

        if (!$form->saveHomeworkQuestion()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not inserted');
        }

        return (new ApiResponse)->success($form->HomeworkQuestionModels, ApiResponse::SUCCESSFUL, 'Record inserted');
    }

    public function actionCreate()
    {
        $model = new Questions;
        $model->attributes = Yii::$app->request->post();
        $model->teacher_id = Yii::$app->user->identity->id;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not validated');
        }

        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record saved');
    }

    public function actionClassQuestions()
    {
        $class_id = Yii::$app->request->get('class_id');
        $subject_id = Yii::$app->request->get('subject_id');
        $topic_id = Yii::$app->request->get('topic_id');
        $form = new \yii\base\DynamicModel(compact('class_id', 'subject_id', 'topic_id'));
        $form->addRule(['class_id', 'subject_id', 'topic_id'], 'required');
        $form->addRule(['class_id', 'subject_id', 'topic_id'], 'exist', ['targetClass' => Questions::className(), 'targetAttribute' => ['class_id' => 'class_id', 'subject_id' => 'subject_id', 'topic_id' => 'topic_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = Questions::find()
            ->where(['subject_id' => $subject_id, 'class_id' => $class_id, 'topic_id' => $topic_id]);

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new \yii\data\ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => 10,
            ]
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionView($question_id)
    {
        $form = new \yii\base\DynamicModel(compact('question_id'));
        $form->addRule(['question_id'], 'required');
        $form->addRule(['question_id'], 'exist', ['targetClass' => Questions::className(), 'targetAttribute' => ['question_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model = Questions::findOne(['id' => $question_id]);

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionDelete($id)
    {
        $model = Questions::findOne(['id' => $id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model->status = SharedConstant::VALUE_ZERO;
        if (!$model->update()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not deleted');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record deleted');
    }

    public function actionUpdate($id)
    {
        $model = Questions::findOne(['id' => $id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not validated');
        }

        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record updated');
    }
}
