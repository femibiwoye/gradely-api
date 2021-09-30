<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Avatar;
use app\modules\v2\models\Country;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\GlobalClass;
use app\modules\v2\models\handler\SessionLogger;
use app\modules\v2\models\notifications\InappNotification;
use app\modules\v2\models\notifications\NotificationOutLogging;
use app\modules\v2\models\Parents;
use app\modules\v2\models\Schools;
use app\modules\v2\models\States;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\TeacherAcademyForm;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\Timezone;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use app\modules\v2\school\models\ClassForm;
use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;


/**
 * Auth controller
 */
class GeneralController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];
        //$behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'except' => [
                'country', 'state', 'timezone', 'global-classes', 'curriculum', 'subject', 'gradely-users-statistics', 'school-auth',
                'register-teacher-academy'
            ]
        ];

        return $behaviors;
    }

    /**
     * Return the status of user. 1=boarded, 0=not boarded.
     * @return ApiResponse
     */
    public function actionBoardingStatus()
    {
        $isBoarded = UserModel::findOne(Yii::$app->user->id)->is_boarded;

        //TODO This populateSubjects here should be removed in production.
        // It's temporarily here for development.
        if (Yii::$app->user->identity->type == 'school') {
            $classForm = new ClassForm();
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            $classForm->populateSubjects($school);
        }


        return (new ApiResponse)->success($isBoarded, null, $isBoarded == 1 ? 'User is boarded' : 'User has not boarded');
    }

    /**
     * Update is_boarded from 0 to 1 when user is boarded.
     * @return ApiResponse
     */
    public function actionUpdateBoarding()
    {
        if (UserModel::updateAll(['is_boarded' => 1], ['id' => Yii::$app->user->id])) {
            if (Yii::$app->user->identity->type == 'school') {
                $classForm = new ClassForm();
                $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
                $classForm->populateSubjects($school);
            }
            return (new ApiResponse)->success(null, null, 'User is successfully boarded');
        } else {
            return (new ApiResponse)->error(null, ApiResponse::NOT_FOUND);
        }
    }

    public function actionUser()
    {
        $user = User::findOne(Yii::$app->user->id);
        if ($user->type == 'school')
            $user = array_merge(ArrayHelper::toArray($user), Utility::getSchoolAdditionalData($user->id));

        elseif ($user->type == 'teacher')
            $user = array_merge(ArrayHelper::toArray($user), Utility::getTeacherAdditionalData($user->id));
        elseif ($user->type == 'student')
            $user = array_merge(ArrayHelper::toArray($user), ['school_class_id' => Utility::getStudentClass()]);

        return (new ApiResponse)->success($user);
    }

    public function actionCountry()
    {
        return (new ApiResponse)->success(Country::find()->all(), ApiResponse::SUCCESSFUL, 'Country loaded');
    }

    public function actionState($country)
    {
        return (new ApiResponse)->success(States::find()->where(['country' => $country])->all());
    }


    public function actionTimezone($area = null)
    {
        $model = Timezone::find();
        if (!empty($area))
            $model = $model->where(['area' => $area]);

        return (new ApiResponse)->success($model->all());
    }

    public function actionTerm()
    {
        //$term = SessionTermOnly::widget(['id' => 12]); // Current terms for users who does belongs to school. 12 is school_id
        //$week = SessionTermOnly::widget(['id' => 12, 'weekOnly' => true]); // Current terms for users who does belongs to school. 12 is school_id
        $term = SessionTermOnly::widget(['nonSchool' => true]); // Current terms for users who does not belong to school
        $week = SessionTermOnly::widget(['nonSchool' => true, 'weekOnly' => true]); // Current terms for users who does not belong to school

        $return = ['term' => $term, 'week' => $week];

        return (new ApiResponse)->success($return);
    }

    public function actionAppNotification()
    {

        $user_id = Yii::$app->user->id;

        $in_app_model = InappNotification::find()->alias('inapp')
            //->select(['inapp.message'])
            // ->innerJoin('notifications', 'notifications.id = inapp.notification_id')
            //->innerJoin('notification_out_logging log', 'log.id = inapp.out_logging_id')
            ->andWhere(['inapp.user_id' => $user_id])
            ->all();

        if (!$in_app_model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No unread notifications!');
        }

        return (new ApiResponse)->success($in_app_model, ApiResponse::SUCCESSFUL, 'Notifications Found!');

    }

    public function actionClearNotification()
    {

        $user_id = Yii::$app->user->id;

        $in_apps_model = InappNotification::find()->alias('inapp')
//            ->innerJoin('notifications', 'notifications.id = inapp.notification_id')
//            ->innerJoin('notification_out_logging log', 'log.id = inapp.out_logging_id')
            ->andWhere(['inapp.user_id' => $user_id])
            ->all();


        //var_dump($in_app_model);die;

        if (!$in_apps_model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No unread notifications!');
        }

        InappNotification::deleteAll(['id' => ArrayHelper::getColumn($in_apps_model, 'id')]);

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Notifications Cleared!');

    }

    public function actionGlobalClasses()
    {
        $classes = GlobalClass::find()->where(['status' => 1])->all();
        return (new ApiResponse)->success($classes, ApiResponse::SUCCESSFUL);
    }

    public function actionAvatar()
    {
        $models = Avatar::find()->where(['status' => 1])->all();
        return (new ApiResponse)->success($models, ApiResponse::SUCCESSFUL);
    }

    public function actionStudentSubscription($child_id = null)
    {
        if (Yii::$app->user->identity->type == 'parent') {
            if ($user = Parents::findOne(['parent_id' => Yii::$app->user->id, 'student_id' => $child_id, 'status' => 1]))
                $user = User::findOne(['id' => $user->student_id, 'type' => 'student']);
            else
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid child id!');
        } else
            $user = Yii::$app->user->identity;

        $model = ['status' => Utility::getSubscriptionStatus($user), 'plan' => $user->subscription_plan, 'expiry' => $user->subscription_expiry];
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL);
    }

    public function actionCurriculum()
    {
        $examType = ExamType::find()
            ->alias('e')
            ->select([
                'id',
                'slug',
                'title'
            ])
            ->where(['e.school_id' => null, 'is_exam' => 0]);

        return (new ApiResponse)->success($examType->asArray()->all(), ApiResponse::SUCCESSFUL);
    }

    public function actionSubject($description = null, $summer = null)
    {

        $select = [
            'id',
            'slug',
            'name'
        ];

        $examType = Subjects::find()
            ->select(
                $description != 1 ? $select : array_merge($select, ['description'])
            )
            ->where(['school_id' => null]);

        if ($summer == 1) {
            $examType = $examType->andWhere(['summer_school' => $summer]);
        }

        return (new ApiResponse)->success($examType->asArray()->all(), ApiResponse::SUCCESSFUL);

    }

    public function actionClassStatus($class_id)
    {
        $user = Yii::$app->user->identity;
        if ($user->type == 'teacher' && TeacherClass::find()->where(['class_id' => $class_id, 'teacher_id' => $user->id, 'status' => 1])->exists()) {
            return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL);
        }
        return (new ApiResponse)->success(false, ApiResponse::SUCCESSFUL);
    }

    /**
     * This returns the user number of student, teachers and time spents by users learning on the platform.
     * @return ApiResponse
     */
    public function actionGradelyUsersStatistics()
    {


//        $start = 5001;
//        $end = 6000;
////return User::find()->where(['type'=>'student'])->andWhere("class >= 1 and class < 12 and id >= $start and id <= $end and created_at < 1627819879")->count();
//foreach(User::find()->where(['type'=>'student'])->andWhere("class >= 1 and class < 12 and id >= $start and id <= $end and created_at < 1627819879")->all() as $key=> $usr){
//    //return $usr;
//    //if($usr->class)
//    $usr->class = $usr->class + 1;
//    $usr->save();
//}
//return $key;

        $studentCount = User::find()->where(['type' => 'student'])->count();
        $teacherCount = User::find()->where(['type' => 'teacher'])->count();

//        $sessions = SessionLogger::find()
//            ->select([
//                'SUM(TIMESTAMPDIFF(SECOND, created_at, updated_at)) AS difference'
//            ])->asArray()->one();
//
//        $studentOnly = SessionLogger::find()
//            ->select([
//                'SUM(TIMESTAMPDIFF(SECOND, created_at, updated_at)) AS difference'
//            ])
//            ->where(['type' => 'student'])->asArray()->one();
//
//        $studentTeacher = SessionLogger::find()
//            ->select([
//                'SUM(TIMESTAMPDIFF(SECOND, created_at, updated_at)) AS difference'
//            ])
//            ->where(['type' => ['student', 'teacher']])->asArray()->one();

        $studentTeacherLearning = SessionLogger::find()
            ->select([
                'SUM(TIMESTAMPDIFF(SECOND, created_at, updated_at)) AS difference'
            ])
            ->where(['type' => ['student', 'teacher']])
            ->andFilterWhere(['OR', ['like', 'url', '%catchup%', false],
                ['like', 'url', '%homework%', false],
                ['like', 'url', '%practice%', false]
            ])
            ->asArray()->one();

        $result = [
            'learningMinutes' => 500000 + floor((int)$studentTeacherLearning['difference'] / 60), //(int)$sessions['difference'] + 507022, //$learningMinutes,
            'teacherCount' => (int)$teacherCount,
            'studentCount' => (int)$studentCount,

//This was used to test
//            'studentOnly' => (int)$studentOnly['difference'],
//            'studentTeacher' => (int)$studentTeacher['difference'],
//            'studentTeacherLearning'=>(int)$studentTeacherLearning['difference']
        ];
        return (new ApiResponse)->success($result, ApiResponse::SUCCESSFUL);
    }

    public function actionSchoolAuth($sch)
    {
        if (!$school = Schools::find()->select(['id', 'name', 'slug', 'logo', 'banner', 'tagline', 'wallpaper'])->where(['slug' => $sch])->asArray()->one()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid school');
        }
        return (new ApiResponse)->success($school, ApiResponse::SUCCESSFUL);
    }

    public function actionRegisterTeacherAcademy($type)
    {
        if (!in_array($type, ['teacher', 'school'])) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Invalid selection');
        }
        $post = Yii::$app->request->post();
        $model = new TeacherAcademyForm();
        $model->attributes = $post;
        $model->type = $type;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->errors, ApiResponse::VALIDATION_ERROR, 'Validation error');
        }
//        $model->name = $post['name'];
//        $model->email = $post['email'];
//        $model->phone = $post['phone'];
//        $model->school_name = $post['school_name'];
//        if ($type == 'school') {
//            $model->role = $post['role'];
//            $model->teacher_count = $post['teacher_count'];
//        }
        if(TeacherAcademyForm::find()->where(['email'=>$post['email']])->exists()){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Already registered');
        }
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Request not successful');
        }
        return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL);
    }
}

