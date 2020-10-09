<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\components\InputNotification;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\TeacherClassSubjects;
use Yii;
use app\modules\v2\models\{Homeworks, Classes, ApiResponse, HomeworkQuestions, Questions};
use app\modules\v2\components\SharedConstant;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\teacher\models\{HomeworkForm, HomeworkQuestionsForm};

class HomeworkController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Homeworks';

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

        //Control user type that can access this
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        return Yii::$app->user->identity->type == SharedConstant::TYPE_TEACHER;
                    },
                ],
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

    public function actionCreate($type)
    {

        if (!in_array($type, SharedConstant::HOMEWORK_TYPES)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid type value');
        }

        $form = new HomeworkForm(['scenario' => 'create-' . $type]);
        $form->attributes = Yii::$app->request->post();

        if (empty($form->class_id)) {
            $form->addError('class_id', 'Provide class_id');
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Provide class ID');
        }

        $schoolID = Classes::findOne(['id' => $form->class_id])->school_id;

        $form->school_id = $schoolID;
        $form->teacher_id = Yii::$app->user->id;

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $typeName = ucfirst($type);

        if (!$model = $form->createHomework($type)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, $typeName . ' record not inserted!');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, $typeName . ' record inserted successfully');
    }

    public function actionUpdate($homework_id)
    {
        $model = $this->modelClass::findOne(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record found!');
        }

        $form = new HomeworkForm(['scenario' => 'update-homework']);
        $form->attributes = Yii::$app->request->post();
        //$form->homework_model = $model;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }
        //$form->attributes = $model->attributes;
        //$form->removeAttachments();

        if (!$model = $form->updateHomework($model)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not updated!');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework record updated successfully');
    }

//    public function actionCreateLesson()
//    {
//        $form = new HomeworkForm;
//        $form->attributes = Yii::$app->request->post();
//        $form->teacher_id = Yii::$app->user->id;
//        $form->attachments = Yii::$app->request->post('lesson_notes');
//        $form->feed_attachments = Yii::$app->request->post('feed_attachments');
//        if (!$form->validate()) {
//            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
//        }
//
//        if (!$model = $form->createHomework('lesson')) {
//            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Lesson record not inserted!');
//        }
//
//        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Lesson record inserted successfully');
//    }

    public function actionClassHomeworks($class_id = null)
    {
        if ($class_id) {
            $model = $this->modelClass::find()->andWhere([
                'teacher_id' => Yii::$app->user->id,
                'class_id' => $class_id,
                'type' => 'homework', 'status' => 1, 'publish_status' => 1]);
        } else
            $model = $this->modelClass::find()->andWhere([
                'teacher_id' => Yii::$app->user->id,
                'type' => 'homework', 'status' => 1, 'publish_status' => 1]);

        if (!$model->count() > 0) {
            return (new ApiResponse)->error([], ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $model->orderBy('id DESC'),
            'pagination' => [
                'pageSize' => 30,
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' record found', $provider);

    }

    public function actionHomeworkDraft($class_id = null)
    {
        if ($class_id) {
            $model = $this->modelClass::find()->andWhere(['teacher_id' => Yii::$app->user->id, 'class_id' => $class_id, 'type' => 'homework', 'status' => 1, 'publish_status' => 0]);
        } else
            $model = $this->modelClass::find()->andWhere(['teacher_id' => Yii::$app->user->id, 'type' => 'homework', 'status' => 1, 'publish_status' => 0]);

        if (!$model->count() > 0) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $model->orderBy('id DESC'),
            'pagination' => [
                'pageSize' => 30,
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' record found', $provider);

    }

    public function actionHomework($homework_id)
    {
        $model = $this->modelClass::find()->andWhere(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id])->one();
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework record found');
    }

    public function actionDeleteHomework($homework_id)
    {
        $model = $this->modelClass::findOne(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        $model->status = SharedConstant::STATUS_DELETED;
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not deleted');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Homework record deleted');
    }

    public function actionExtendDate($homework_id)
    {
        $open_Date = Yii::$app->request->post('open_date');
        $close_date = Yii::$app->request->post('close_date');
        $model = Homeworks::find()->andWhere(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1])->one();
        if (!$model || ($model->teacher_id != Yii::$app->user->id)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        if (strtotime($close_date) <= time()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Close date should not be in the past.');
        }

        $model->close_date = $close_date;
        $model->open_date = $open_Date;
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework date not update');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework close date updated');
    }

    public function actionRestartHomework($homework_id)
    {
        $password = Yii::$app->request->post('password');


        if (empty($password)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Password is required');
        }

        if (!Yii::$app->user->identity->validatePassword($password)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Password is not correct!');
        }

        $model = Homeworks::findOne(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        if (!$model->getRestartHomework()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not restarted');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework record restarted successfully.');
    }

    public function actionPublishHomework($homework_id)
    {
        $model = Homeworks::findOne(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        if ($model->publish_status == 1) {
            return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework already published');
        }

        if ($model->publish_status == 0) {
            $model->publish_status = 1;
            if ($model->save())
                return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework successfully published');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Something went wrong.');
    }

    public function actionSubject($class_id)
    {
        $subjects = TeacherClassSubjects::find()->where(['teacher_id' => Yii::$app->user->id, 'class_id' => $class_id, 'status' => 1])->groupBy('subject_id')->all();
        $subjects = Subjects::findAll(['id' => ArrayHelper::getColumn($subjects, 'subject_id'), 'status' => 1]);
        if (!$subjects) {
            return (new ApiResponse)->error(null, ApiResponse::NO_CONTENT, 'No subject found');
        }

        return (new ApiResponse)->success($subjects, ApiResponse::SUCCESSFUL, count($subjects) . ' subjects found');
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record validation failed');
        }

        if (!$form->saveHomeworkQuestion()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not inserted');
        }

        return (new ApiResponse)->success($form->HomeworkQuestionModels, ApiResponse::SUCCESSFUL, 'Record inserted');
    }

    public function actionQuestion()
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
}
