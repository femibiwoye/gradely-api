<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Country;
use app\modules\v2\models\notifications\InappNotification;
use app\modules\v2\models\notifications\NotificationOutLogging;
use app\modules\v2\models\Schools;
use app\modules\v2\models\States;
use app\modules\v2\models\Timezone;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use app\modules\v2\school\models\ClassForm;
use Yii;
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
            'except' => ['country', 'state', 'timezone']
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

        return (new ApiResponse)->success($user);
    }

    public function actionCountry()
    {
        return (new ApiResponse)->success(Country::find()->all());
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
        $term = SessionTermOnly::widget(['nonSchool' => true]); // Current terms for users who does not belong to school
        $week = SessionTermOnly::widget(['nonSchool' => true,'weekOnly' => true]); // Current terms for users who does not belong to school

        $return = ['term'=>$term,'week'=>$week];

        return (new ApiResponse)->success($return);
    }

    public function actionAppNotification(){

        $user_id = Yii::$app->user->id;

        $in_app_model = InappNotification::find()->alias('inapp')
            ->select(['inapp.message'])
            ->innerJoin('notifications', 'notifications.id = inapp.notification_id')
            ->innerJoin('notification_out_logging log', 'log.id = inapp.out_logging_id')
            ->andWhere(['inapp.user_id' => $user_id, 'log.status' => 1, 'log.notification_type' => 'app'])
            ->all();

        if(!$in_app_model){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No unread notifications!');
        }

        return (new ApiResponse)->success($in_app_model, ApiResponse::SUCCESSFUL, 'Notifications Found!');

    }

    public function actionClearNotification(){

        $user_id = Yii::$app->user->id;

        $in_apps_model = InappNotification::find()->alias('inapp')
            ->innerJoin('notifications', 'notifications.id = inapp.notification_id')
            ->innerJoin('notification_out_logging log', 'log.id = inapp.out_logging_id')
            ->andWhere(['inapp.user_id' => $user_id, 'log.status' => 1, 'log.notification_type' => 'app'])
            ->all();


        //var_dump($in_app_model);die;

        if(!$in_apps_model){
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No unread notifications!');
        }

        foreach ($in_apps_model as $in_app_model){

            InappNotification::findOne($in_app_model->id)->delete();
            NotificationOutLogging::findOne($in_app_model->out_logging_id)->delete();

        }
        return (new ApiResponse)->success('', ApiResponse::SUCCESSFUL, 'Notifications Cleared!');

    }
}

