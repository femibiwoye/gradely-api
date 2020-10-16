<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Feed;
use app\modules\v2\models\Homeworks;
use app\modules\v2\models\InviteLog;
use app\modules\v2\models\RequestCall;
use app\modules\v2\models\SchoolNamingFormat;
use app\modules\v2\models\SchoolRole;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolTeachers;
use app\modules\v2\models\SchoolType;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TutorSession;
use app\modules\v2\models\{User, UserProfile, SchoolCurriculum};
use app\modules\v2\school\models\SchoolProfile;
use app\modules\v2\components\SharedConstant;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


/**
 * Schools/Parent controller
 */
class GeneralController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\SchoolType';
    //public $modelFormat = 'app\modules\v2\models\SchoolNamingFormat';

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

        //Control user type that can access this
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'rules' => [
                [
                    'allow' => true,
                    'matchCallback' => function () {
                        return Yii::$app->user->identity->type == 'school';
                    },
                ],
            ],
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


    public function actionSummary($startRange = null, $endRange = null)
    {

        $dateTime = date('Y-m-d H:i:s');

        $allHomeWorkCount = Homeworks::find()->where(['school_id' => Utility::getSchoolAccess(), 'publish_status' => 1, 'status' => 1])->count();

        $pastHomework = Homeworks::find()
            ->where(['AND', ['school_id' => Utility::getSchoolAccess(), 'status' => 1], ['<', 'close_date', $dateTime]
            ])->count();

        $activeHomeWork = Homeworks::find()->where(['AND',
            ['school_id' => Utility::getSchoolAccess(), 'publish_status' => 1, 'access_status' => 1, 'status' => 1],
            ['>', 'close_date', $dateTime],
            ['<', 'open_date', $dateTime],
        ])->count();

        $yetToStartHomeWork = Homeworks::find()->where([
            'AND',
            [
                'school_id' => Utility::getSchoolAccess(),
                'status' => 1,
                'publish_status' => 1],
            ['>', 'open_date', $dateTime]
        ])->count();


        $schoolClasses = ArrayHelper::getColumn(Classes::find()->where(['school_id' => Utility::getSchoolAccess()])->all(), 'id');

        $liveClassSessions = TutorSession::find()
            ->where(['is_school' => 1, 'category' => 'class', 'class' => $schoolClasses])
            ->count();

        $pendingSessions = TutorSession::find()
            ->where(['is_school' => 1, 'category' => 'class', 'class' => $schoolClasses, 'status' => 'pending'])
            ->count();


        $ongoingSessions = TutorSession::find()
            ->where(['is_school' => 1, 'category' => 'class', 'class' => $schoolClasses, 'status' => 'ongoing'])
            ->count();


        $completedSessions = TutorSession::find()
            ->where(['is_school' => 1, 'category' => 'class', 'class' => $schoolClasses, 'status' => 'completed'])
            ->count();


        $data = [
            'allHomework' => $allHomeWorkCount,
            'pastHomework' => $pastHomework,
            'activeHomeWork' => $activeHomeWork,
            'yetToStartHomeWork' => $yetToStartHomeWork,
            //'homeworkRange' => $homeworkRange,
            'liveClassSessions' => $liveClassSessions,
            'pendingSessions' => $pendingSessions,
            'ongoingSessions' => $ongoingSessions,
            'completedSessions' => $completedSessions
        ];

        return (new ApiResponse)->success($data, ApiResponse::SUCCESSFUL);
    }

    /**
     * This returns school types.
     * e.g primary, secondary, primary and secondary
     * @return ApiResponse
     */
    public function actionSchoolType()
    {
        return (new ApiResponse)->success(SchoolType::find()->where(['status' => 1])->all(), ApiResponse::SUCCESSFUL, 'Found');
    }

    /**
     * This returns the format to be used in naming the classes.
     * E.g Primary, Junior Secondary school, Senior Secondary school OR Year1-12
     * @return ApiResponse
     */
    public function actionSchoolNamingFormat()
    {
        return (new ApiResponse)->success(SchoolNamingFormat::find()->where(['status' => 1])->all(), ApiResponse::SUCCESSFUL, 'Found');
    }

    public function actionUpdateFormatType()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $form = new SchoolProfile(['scenario' => 'format-type']);
        $form->attributes = Yii::$app->request->post();

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$school = $form->updateFormats($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class is not updated');
        }

        return (new ApiResponse)->success($school);
    }

    /**
     * Get list of all available school roles
     * @return ApiResponse
     */
    public function actionSchoolRoles()
    {
        return (new ApiResponse)->success(SchoolRole::find()->select('title, slug')->where(['status' => 1])->all(), ApiResponse::SUCCESSFUL, 'Found');

    }

    /**
     * School can request a call from this endpoint to gradely, either for demo or others.
     * @return ApiResponse
     */
    public function actionRequestCall()
    {

        $form = new RequestCall(['scenario' => 'new-call']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $form->user_id = $school->id;
        if (!$form->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not successfully requested.');
        }

        return (new ApiResponse)->success($form);

    }

    public function actionDashboardTodoStatus()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $teacher = InviteLog::find()->where(['sender_id' => $school->id, 'receiver_type' => 'teacher'])->exists() || SchoolTeachers::find()->where(['school_id' => $school->id])->exists();
        $student = StudentSchool::find()->where(['school_id' => $school->id, 'status' => 1])->exists();
        $announcement = Feed::find()->where(['type' => 'announcement', 'user_id' => $school->id])->exists();
        $profile = !empty($school->tagline);

        return (new ApiResponse)->success(['teacher' => $teacher, 'student' => $student, 'announcement' => $announcement, 'profile' => $profile]);
    }
}