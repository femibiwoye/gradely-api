<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\{InputNotification, Pricing, Utility};
use app\modules\v2\models\Parents;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\TeacherClassSubjects;
use Yii;
use app\modules\v2\models\{GlobalClass, Homeworks, Classes, ApiResponse, HomeworkQuestions, Questions, Feed};
use app\modules\v2\components\SharedConstant;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
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
                CustomHttpBearerAuth::className(),
            ],
        ];

        //Control user type that can access this
//        $behaviors['access'] = [
//            'class' => AccessControl::className(),
//            'rules' => [
//                [
//                    'allow' => true,
//                    'matchCallback' => function () {
//                        return Yii::$app->user->identity->type == SharedConstant::TYPE_TEACHER;
//                    },
//                ],
//            ],
//        ];


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
//        if (!Pricing::SubscriptionStatus()) {
//            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No active subscription');
//        }

        if (!in_array($type, SharedConstant::HOMEWORK_TYPES)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid type value');
        }

        $form = new HomeworkForm(['scenario' => 'create-' . $type]);
        $form->attributes = Yii::$app->request->post();

        if (empty($form->class_id)) {
            $form->addError('class_id', 'Provide class_id');
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Provide class ID');
        }

        if (!is_array($form->class_id)) {
            $classIDs = [$form->class_id];
        } else {
            $classIDs = $form->class_id;
        }

        $uniqueIdentifier = time() . mt_rand(1000, 9999);
        foreach ($classIDs as $classID) {

            $form->class_id = $classID;
            $form->bulk_creation_reference = $uniqueIdentifier;
            $schoolID = Classes::findOne(['id' => $classID])->school_id;

            $form->school_id = $schoolID;
            $form->teacher_id = Yii::$app->user->id;

            if (!$form->validate()) {
                return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
            }

            $typeName = ucfirst($type);

            if (!$model = $form->createHomework($type)) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, $typeName . ' record not inserted!');
            }
        }

//        } catch (\Exception $e) {
//            $dbtransaction->rollBack();
//            return (new ApiResponse)->error($e, ApiResponse::UNABLE_TO_PERFORM_ACTION);
//        }


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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
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
//            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
//        }
//
//        if (!$model = $form->createHomework('lesson')) {
//            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Lesson record not inserted!');
//        }
//
//        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Lesson record inserted successfully');
//    }

    public function actionClassHomeworks($class_id = null, $subject_id = null, $type = null, $sort = null, $status = null)
    {
        $model = $this->modelClass::find()->andWhere([
            'teacher_id' => Yii::$app->user->id,
            'type' => 'homework', 'status' => 1, 'publish_status' => 1])
            ->andWhere(['OR', ['tag' => 'exam', 'review_status' => 1], ['tag' => ['homework', 'quiz']]]);
        if ($class_id) {
            $model = $model->andWhere(['class_id' => $class_id]);
        }

        if ($type) {
            $model = $model->andWhere(['tag' => $type]);
        }

        //TODO add status and type filter here

        if ($subject_id) {
            $model = $model->andWhere(['subject_id' => $subject_id]);
        }

//        if(!empty($term) && in_array($term,SharedConstant::TERMS)){
//            $model = $model->andWhere(['term'=>$term]);
//        }

        if ($status == 'open') {
            $model = $model->andWhere(['>', 'UNIX_TIMESTAMP(close_date)', time()]);
        } else if ($status == 'closed') {
            $model = $model->andWhere(['<', 'UNIX_TIMESTAMP(close_date)', time()]);
        }

        if ($sort == 'a-z') {
            $model = $model->orderBy(['title' => SORT_ASC]);
        } elseif ($sort == 'z-a') {
            $model = $model->orderBy(['title' => SORT_DESC]);
        } elseif ($sort == 'subject') {
            $model = $model->orderBy(['subject_id' => SORT_ASC]);
        } elseif ($sort == 'recent') {
            $model = $model->orderBy(['publish_at' => SORT_DESC]);
        } else {
            $model = $model->orderBy('id DESC');
        }



        if (!$model->count() > 0) {
            return (new ApiResponse)->error([], ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $model,
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

    public function actionHomeworkReview($class_id = null, $subject_id = null, $type = null, $sort = null)
    {
        $model = $this->modelClass::find()->andWhere([
            'teacher_id' => Yii::$app->user->id,
            'type' => 'homework', 'status' => 1, 'publish_status' => 1])
            ->andWhere(['OR', ['tag' => 'exam', 'review_status' => 0], ['tag' => ['homework', 'quiz']]]);
        if ($class_id) {
            $model = $model->andWhere(['class_id' => $class_id]);
        }

        if ($type) {
            $model = $model->andWhere(['tag' => $type]);
        }

        //TODO add status and type filter here

        if ($subject_id) {
            $model = $model->andWhere(['subject_id' => $subject_id]);
        }

//        if(!empty($term) && in_array($term,SharedConstant::TERMS)){
//            $model = $model->andWhere(['term'=>$term]);
//        }


        if ($sort == 'a-z') {
            $model = $model->orderBy(['title' => SORT_ASC]);
        } elseif ($sort == 'z-a') {
            $model = $model->orderBy(['title' => SORT_DESC]);
        } elseif ($sort == 'subject') {
            $model = $model->orderBy(['subject_id' => SORT_ASC]);
        } elseif ($sort == 'recent') {
            $model = $model->orderBy(['publish_at' => SORT_DESC]);
        } else {
            $model = $model->orderBy('id DESC');
        }


        if (!$model->count() > 0) {
            return (new ApiResponse)->error([], ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $model,
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

    public function actionClassFilteredAssessment($class_id = null, $term = null, $subject_id = null, $session = null)
    {
        $model = Homeworks::find()
            ->select([
                'homeworks.created_at',
                'title',
                'tag',
                'homeworks.id',
//                new Expression('round((SUM(case when qsd.selected = qsd.answer then 1 else 0 end)/COUNT(qsd.id))*100) as average_score'), //To be removed eventually
                new Expression('round((SUM(qsd.is_correct)/COUNT(qsd.id))*100) as average_score'),
            ])
            ->leftJoin('quiz_summary_details qsd', 'qsd.homework_id = homeworks.id')
            ->andWhere([
                'teacher_id' => Yii::$app->user->id,
                'type' => 'homework', 'status' => 1, 'publish_status' => 1]);
        if ($class_id) {
            $model = $model->andWhere(['class_id' => $class_id]);
        }

        if ($subject_id) {
            $model = $model->andWhere(['subject_id' => $subject_id]);
        }


        if (!$model->count() > 0) {
            return (new ApiResponse)->error([], ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $model->asArray()->groupBy('homeworks.id')->orderBy('homeworks.id DESC'),
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
        if (Yii::$app->user->identity->type == 'school') {
            $model = $this->modelClass::findOne(['id' => $homework_id, 'school_id' => Utility::getSchoolAccess(), 'status' => 1]);
        } else {
            $model = $this->modelClass::findOne(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1]);
        }

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not found');
        }

        $model->status = SharedConstant::STATUS_DELETED;
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            $homework_id = $model->id;
            if (!$model->delete()) {
                return false;
            }

            if (!$this->deleteHomeworkFeed($homework_id)) {
                return false;
            }

            $dbtransaction->commit();
        } catch (Exception $e) {
            $dbtransaction->rollBack();
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework record not deleted');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Homework record deleted');
    }

    private function deleteHomeworkFeed($homework_id)
    {
        $model = Feed::findOne(['reference_id' => $homework_id, 'type' => 'homework']);
        if (!$model) {
            return true;
        }

        $model->status = SharedConstant::STATUS_DELETED;
        if (!$model->delete()) {
            return false;
        }

        return true;
    }

    public function actionExtendDate($homework_id)
    {
        $open_Date = Yii::$app->request->post('open_date');
        $close_date = Yii::$app->request->post('close_date');
        $model = Homeworks::find()->andWhere(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1])->one();
        if (!$model || ($model->teacher_id != Yii::$app->user->id || in_array($model->school_id, Utility::getSchoolAccess()))) {
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

//        if (!Yii::$app->user->identity->validatePassword($password)) {
        if ($password != 'restart') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Password is not correct!');
        }

        if (Yii::$app->user->identity->type == 'school') {
            $model = Homeworks::findOne(['id' => $homework_id, 'status' => 1, 'school_id' => Utility::getSchoolAccess()]);
        } else {
            $model = Homeworks::findOne(['id' => $homework_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1]);
        }
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

        $createStatus = false;
        if ($model->publish_status == 0) {
            $model->publish_status = 1;
            $model->publish_at = date('Y-m-d H:i:s');
            if ($model->save() && $this->publishHomeworkFeed($model->id, $model)) {
                $this->HomeworkNotification($model);
            } else {
                $createStatus = true;
            }
        }


        foreach (Homeworks::find()->where(['bulk_creation_reference' => $model->bulk_creation_reference])->andWhere(['!=', 'id', $model->id])->all() as $eachHomework) {

            $homeworkQuestions = HomeworkQuestions::find()->where(['homework_id' => $model->id])->all();
            foreach ($homeworkQuestions as $eachQuestion) {
                $hqModel = new HomeworkQuestions();
                $hqModel->attributes = $eachQuestion->attributes;
                $hqModel->homework_id = $eachHomework->id;
                $hqModel->save();
            }

            if ($eachHomework->publish_status == 0) {
                $eachHomework->publish_status = 1;
                $eachHomework->publish_at = date('Y-m-d H:i:s');
                if ($eachHomework->save() && $this->publishHomeworkFeed($eachHomework->id, $eachHomework)) {
                    $this->HomeworkNotification($eachHomework);
                    //Call success notification
                } else {
                    $createStatus = true;
                }
            }
        }
        if ($createStatus) {
            return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Something went wrong.');
        }
        return (new ApiResponse)->success($eachHomework, ApiResponse::SUCCESSFUL, 'Homework successfully published');
    }

    private function publishHomeworkFeed($homework_id, $Homework)
    {
        if ($Homework->tag != 'exam') {
            $model = Feed::findOne(['reference_id' => $homework_id, 'status' => SharedConstant::VALUE_ZERO]);
            $model->status = SharedConstant::VALUE_ONE;
            $model->created_at = date('Y-m-d H:i:s');
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }

    private function HomeworkNotification($model)
    {
//        if($model->publish_status == SharedConstant::VALUE_ZERO){
//
//            $notification = new InputNotification();
//            if (!$notification->NewNotification('homework_draft_teacher', [['homework_id', $model->id]]))
//                return false;
//        }

        if ($model->tag == 'exam') {
//            $notification = new InputNotification();
//            $notification->NewNotification('exam_scheduled_parent', [
//                ['parent_name', $parent->parentProfile->firstname . ' ' . $parent->parentProfile->lastname],
//                ['child_name', $student->firstname . ' ' . $student->lastname],
//                ['subject', $tutorSession->subject->name],
//                ['duration', $tutorSession->classObject->class_name],
//                ['email', $parent->parentProfile->email]
//            ]);
        } else {
            $notification = new InputNotification();
            $notification->NewNotification('teacher_create_homework_teacher', [['homework_id', $model->id]]);

            //Get all the students in that class
            $classStudent = StudentSchool::find()->where(['class_id' => $model->class_id, 'status' => 1, 'is_active_class' => 1])->exists();

            //foreach ($classStudents as $classStudent){

            if ($classStudent) {
                $notification = new InputNotification();
                $notification->NewNotification('teacher_create_homework_student', [['homework_id', $model->id]]);
                $notification->NewNotification('teacher_create_homework_parent', [['homework_id', $model->id]]);
            }
        }

        // }
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
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
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Record validation failed');
        }

        if (!$form->saveHomeworkQuestion()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not inserted');
        }

        return (new ApiResponse)->success($form->HomeworkQuestionModels, ApiResponse::SUCCESSFUL, 'Record inserted');
    }

    /**
     * For classes a teacher should be able to select from
     * @param $class_id
     * @return ApiResponse
     */
    public function actionGetRelatedClasses($global_class)
    {
        $end = $global_class + 2;
        $start = $global_class - 2;
        if ($global_class >= 11) {
            $end = 12;
        }
        if ($global_class < 2 || $global_class > 12) {
            $start = 13;
            $end = 15;
        }

        $numbers = [];

        foreach (range($start, $end) as $number) {
            $numbers[] = $number;
        }
        if ($global_class < 2) {
            $numbers = array_merge($numbers, [1, 2]);
        } elseif ($global_class == 2) {
            $numbers = [15, 1, 2, 3, 4];
        }
        return (new ApiResponse)->success(GlobalClass::findAll(['id' => $numbers]), ApiResponse::SUCCESSFUL, 'Possible topics to be seen');
    }

    /**
     * Not sure what is function is meant for.
     * @return ApiResponse
     */
    /*public function actionQuestion()
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
    }*/
}
