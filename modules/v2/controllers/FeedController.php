<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\Utility;
use app\modules\v2\models\Parents;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\teacher\models\PostForm;
use frontend\components\GoogleCalendar;
use frontend\modules\teachers\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\models\{Feed, ApiResponse, Homeworks, TutorSession, FeedComment, FeedLike, Classes, SchoolTeachers};
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

    public function actionIndex($class_id = null, $token = null, $student_id = null)
    {
        $me = Yii::$app->request->get('me');
        $subject_id = Yii::$app->request->get('subject_id');
        $homework = Yii::$app->request->get('homework');
        $teacher = Yii::$app->request->get('teacher');
        $student = Yii::$app->request->get('student');
        $type = Yii::$app->request->get('type');
        if (Yii::$app->user->identity->type == 'teacher') {
            $teacher_id = Yii::$app->user->id;
            $status = 1;
            if (empty($class_id))
                $class_id = isset(Utility::getTeacherClassesID(Yii::$app->user->id)[0]) ? Utility::getTeacherClassesID(Yii::$app->user->id)[0] : [];

            $validate = new \yii\base\DynamicModel(compact('class_id', 'teacher_id', 'status'));
            $validate
                ->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id', 'teacher_id', 'status']]);
            if (!$validate->validate()) {
                return (new ApiResponse)->error($validate->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
            }

            $models = $this->modelClass::find()
                ->where(['class_id' => $class_id, 'view_by' => ['all', 'class']])->orWhere(['AND', ['user_id' => Yii::$app->user->id], ['is', 'class_id', new \yii\db\Expression('null')]]);

        } else if (Yii::$app->user->identity->type == 'school') {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            $school_id = $school->id;
            $validate = new \yii\base\DynamicModel(compact('class_id', 'school_id'));
            $validate
                ->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id', 'school_id']]);
            if (!$validate->validate()) {
                return (new ApiResponse)->error($validate->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
            }

            if (empty($class_id))
                $class_id = ArrayHelper::getColumn(Classes::find()
                    ->where(['school_id' => Utility::getSchoolAccess()])->all(), 'id')[0];

            $models = $this->modelClass::find();

            if ($teacher || $student) {
                //Return all teacher feeds
                if ($teacher) {
                    $teachers = ArrayHelper::getColumn(
                        SchoolTeachers::find()->where(['school_id' => Yii::$app->user->id])->all(),
                        'teacher_id'
                    );

                    $models = $this->modelClass::find()
                        ->innerJoin('user', 'user.id = feed.user_id')
                        ->where(['user.type' => 'teacher'])
                        ->andWhere(['feed.user_id' => $teachers])
                        ->where(['AND', ['feed.class_id' => $class_id], ['not', ['feed.class_id' => null]]])
                        ->orWhere(['AND', ['feed.user_id' => Utility::allSchoolUserID(Utility::getSchoolAccess())], ['is', 'feed.class_id', new \yii\db\Expression('null')]]);

                }

                // Return all student and parent feed
                if ($student) {
                    $models = $this->modelClass::find()
                        ->innerJoin('user', 'user.id = feed.user_id')
                        ->where(['user.type' => ['student', 'parent']])
                        ->where(['AND', ['feed.class_id' => $class_id], ['not', ['feed.class_id' => null]]])
                        ->orWhere(['AND', ['feed.user_id' => Utility::allSchoolUserID(Utility::getSchoolAccess())], ['is', 'feed.class_id', new \yii\db\Expression('null')]]);
                }
            } else {
                $models = $this->modelClass::find()
                    ->where(['AND', ['class_id' => $class_id], ['not', ['class_id' => null]]])
                    ->orWhere(['AND', ['user_id' => Utility::allSchoolUserID(Utility::getSchoolAccess())], ['is', 'class_id', new \yii\db\Expression('null')]]);
            }

        } elseif (Yii::$app->user->identity->type == 'student') {
            $user_id = Yii::$app->user->id;
            $class = StudentSchool::findOne(['student_id' => $user_id,'status'=>1]);
            if ($class) {
                $class_id = $class->class_id;
            }

            $models = $this->modelClass::find()
                ->where(['class_id' => $class_id, 'view_by' => ['all', 'class', 'student']]);
        } elseif (Yii::$app->user->identity->type == 'parent') {
            //The class_id is used as student_id
            $user_id = Yii::$app->user->id;
            $parents = ArrayHelper::getColumn(Parents::find()->where(['student_id' => $class_id, 'parent_id' => $user_id])->all(), 'student_id');

            if (!in_array($class_id, $parents)) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child does not exist');
            }
            $classes = ArrayHelper::getColumn(StudentSchool::find()->where(['student_id' => $parents, 'status' => 1])->all(), 'class_id');


            $models = $this->modelClass::find()
                ->where(['class_id' => $classes, 'view_by' => ['all', 'parent', 'student', 'class']]);
        }

        //To filter by student id
        if ($student_id) {
            $models = $models
                ->innerJoin('user', 'user.id = feed.user_id')
                ->andWhere([
                    'feed.user_id' => $student_id,
                    'feed.class_id' => $class_id,
                    'user.type' => 'student'
                ]);
        }

        if ($type)
            $models = $models->andWhere(['type' => $type]);

        if ($me && $me != SharedConstant::VALUE_ZERO)
            $models = $models->andWhere(['user_id' => Yii::$app->user->id]);

        if ($subject_id)
            $models = $models->andWhere(['subject_id' => $subject_id]);

        if ($homework && $homework != SharedConstant::VALUE_ZERO)
            $models = $models->andWhere(['type' => SharedConstant::FEED_TYPES[2]]);

        if ($token && $oneMmodels = $models->andWhere(['token' => $token])->one()) {
            $comments = FeedComment::find()->where(['feed_id' => $oneMmodels->id, 'type' => 'feed'])->orderBy('id')->all();
            return (new ApiResponse)->success(array_merge(ArrayHelper::toArray($oneMmodels), ['comment' => $comments]), ApiResponse::SUCCESSFUL, 'Found');
        }

        if (!$models->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Feeds not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $models->orderBy('id DESC'),
            'pagination' => [
                'pageSize' => 3,
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' Feeds found for ' . Yii::$app->user->identity->type, $provider);
    }

    public function actionUpcoming()
    {
        $new_announcements = array_merge((new Homeworks)->getnewHomeworks(), (new TutorSession)->getNewSessions());
        if (!$new_announcements) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No record found!');
        }

        array_multisort(array_column($new_announcements, 'date_time'), $new_announcements);
        return (new ApiResponse)->success($new_announcements, ApiResponse::SUCCESSFUL, count($new_announcements) . ' records found!');
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
        $model = FeedComment::findOne(['id' => $comment_id, 'type' => 'feed']);
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
        $userType = Yii::$app->user->identity->type;
        if ($userType == 'student' || $userType == 'parent')
            $scenario = 'student-parent';
        elseif ($userType == 'teacher')
            $scenario = 'teacher';
        else
            $scenario = 'school';

        $model = new PostForm(['scenario' => $scenario]);
        if (Yii::$app->request->post('type'))
            $model->type = Yii::$app->request->post('type');
        if (Yii::$app->request->post('class_id'))
            $model->class_id = Yii::$app->request->post('class_id');
        if (Yii::$app->request->post('view_by'))
            $model->view_by = Yii::$app->request->post('view_by');
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $header = $model->type == 'post' ? 'Discussion' : 'Announcement';

        if (!$response = $model->newPost()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, $header . ' not made');
        }

        return (new ApiResponse)->success(ArrayHelper::toArray($response), ApiResponse::SUCCESSFUL, $header . ' made successfully');
    }

    public function actionNewLiveClass()
    {
        $school_id = Utility::getTeacherSchoolID(Yii::$app->user->id);
        if (!$school_id) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission failed');
        }

        $tutor_session = TutorSession::find()
            ->innerJoin('school_teachers', 'school_teachers.teacher_id = tutor_session.requester_id')
            ->where(['school_teachers.school_id' => $school_id, 'is_school' => 1, 'school_teachers.teacher_id' => Yii::$app->user->id])
            ->andWhere('YEARWEEK(tutor_session.created_at) = YEARWEEK(NOW())')
            ->andFilterWhere(['OR', "IF(tutor_session.meta = 'recommendation', IF(tutor_session.participant_type = 'single', 1, 0), 0) = 0"])
            ->count();

        if ($tutor_session >= Yii::$app->params['live_class_limit']) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, "You've had the maximum eligible live class limit");
        }

        $model = new TutorSession(['scenario' => 'new-class']);
        $model->attributes = Yii::$app->request->post();
        $model->requester_id = Yii::$app->user->id;
        $model->category = 'class';
        $model->is_school = 1;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }
        $model->class = $model->class_id;

        if (!$model->scheduleClass($model)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not created!');
        }
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL);
    }

    public function actionUpdateLiveClassAvailability($id)
    {
        $availability = Yii::$app->request->post('availability');
        if (!$availability) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Availability time cannot be blank!');
        }

        $model = TutorSession::find()
            ->where(['id' => $id, 'requester_id' => Yii::$app->user->id])
            ->one();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model->availability = $availability;
        if (!$model->save()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record updated');
    }

    public function actionDeleteLiveClass($id)
    {
        $model = TutorSession::findOne(['id' => $id, 'requester_id' => Yii::$app->user->id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        if (!$model->delete()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not deleted');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Record deleted successfully!');
    }

    public function actionUpdateLiveClassDetails($id)
    {
        $subject_id = Yii::$app->request->post('subject_id');
        $title = Yii::$app->request->post('title');
        $validate = new \yii\base\DynamicModel(compact('subject_id', 'title'));
        $validate->addRule(['subject_id', 'title'], 'required');
        if (!$validate->validate()) {
            return (new ApiResponse)->error($validate->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = TutorSession::find()
            ->where(['id' => $id, 'requester_id' => Yii::$app->user->id])
            ->one();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model->subject_id = $subject_id;
        $model->title = $title;
        if (!$model->save()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record updated successfully');
    }

    /**
     *
     * Delete records from feed
     *
     * @param $feed_id
     * @return ApiResponse
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDeleteFeed($feed_id)
    {
        $feed = Feed::findOne(['id' => $feed_id, 'status' => 1]);
        if (!$feed) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not found');
        }

        if ($feed->user_id != Yii::$app->user->id)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid access');

        if (!$feed->delete())
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not delete this record');

        return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL);
    }
}
