<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\models\SubjectTopics;
use Yii;
use app\modules\v2\models\{Homeworks, Classes, ApiResponse};
use app\modules\v2\components\SharedConstant;
use yii\filters\AccessControl;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\teacher\models\HomeworkForm;

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

    public function actionCreate()
    {
        $form = new HomeworkForm(['scenario' => 'create-homework']);
        $form->attributes = Yii::$app->request->post();

        $schoolID = Classes::findOne(['id' => $form->class_id])->school_id;

        $form->school_id = $schoolID;
        $form->teacher_id = Yii::$app->user->id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model = $form->createHomework()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not inserted!');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Homework record inserted successfully');
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

    public function actionCreateLesson()
    {
        $form = new HomeworkForm;
        $form->attributes = Yii::$app->request->post();
        $form->teacher_id = Yii::$app->user->id;
        $form->homework_type = SharedConstant::FEED_TYPES[3];
        $form->attachments = Yii::$app->request->post('lesson_notes');
        $form->feed_attachments = Yii::$app->request->post('feed_attachments');
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model = $form->createHomework()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Lesson record not inserted!');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Lesson record inserted successfully');
    }

    public function actionClassHomeworks($class_id = null)
    {
        if ($class_id) {
            $model = $this->modelClass::find()->where(['teacher_id' => Yii::$app->user->id, 'class_id' => $class_id, 'type' => 'homework', 'status' => 1])->all();
        } else
            $model = $this->modelClass::find()->where(['teacher_id' => Yii::$app->user->id, 'type' => 'homework', 'status' => 1])->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, count($model) . ' records found');
    }

    public function actionHomework($homework_id)
    {
        $model = $this->modelClass::find()->where(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id])->one();
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
        $close_date = Yii::$app->request->post('close_date');
        $model = Homeworks::find()->where(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1])->one();
        if (!$model || ($model->teacher_id != Yii::$app->user->id)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        if (strtotime($close_date) <= time()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Close date should not be in the past.');
        }

        $model->close_date = $close_date;
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
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Password cannot be validated!');
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
}
