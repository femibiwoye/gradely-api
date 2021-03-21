<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Homeworks;
use app\modules\v2\models\InviteLog;
use app\modules\v2\models\notifications\InappNotification;
use app\modules\v2\models\Parents;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\User;
use app\modules\v2\student\models\StudentHomeworkReport;
use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use app\modules\v2\models\{SecurityQuestions, ApiResponse, SecurityQuestionAnswer};


/**
 * Schools/Parent controller
 */
class GeneralController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\User';

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

    public function actionSecurityQuestions()
    {
        $models = SecurityQuestions::find()->all();
        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($models, ApiResponse::SUCCESSFUL, 'Record not found');
    }

    public function actionSetSecurityQuestion()
    {
        if (!$model = SecurityQuestionAnswer::findOne(['user_id' => Yii::$app->user->id]))
            $model = new SecurityQuestionAnswer;
        $model->question = Yii::$app->request->post('question');
        $model->user_id = Yii::$app->user->id;
        $model->answer = Yii::$app->request->post('answer');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Data not validated');
        }

        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Security answer not saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Security answer saved');
    }

    /**
     * Get parent and student dashboard status on their todo
     * @return ApiResponse
     */
    public function actionDashboardTodo()
    {
        ///Take practice appears on both
        //Invite school school for parent
        //Invite parent for student
        $studentID = Utility::getParentChildID();

        $takePractice = QuizSummary::find()->where(['student_id' => $studentID, 'submit' => 1])->exists();
        $inviteSchool = InviteLog::find()->where(['sender_id' => Yii::$app->user->id, 'receiver_type' => 'school'])->exists();
        $inviteParent = InviteLog::find()->where(['sender_id' => Yii::$app->user->id, 'sender_type' => 'student', 'receiver_type' => 'parent'])->exists() || Parents::find()->where(['student_id' => Yii::$app->user->id, 'status' => 1])->exists();
        $connectedToSchool = StudentSchool::find()->where(['student_id' => $studentID, 'status' => 1])->exists();

        return (new ApiResponse)->success(['takePractice' => $takePractice, 'inviteSchool' => $inviteSchool, 'inviteParent' => $inviteParent, 'connectedToSchool' => $connectedToSchool], ApiResponse::SUCCESSFUL);
    }

    /**
     * This gets school and class details of a child.
     * Can be used by student and parent
     *
     * @param null $child
     * @return ApiResponse
     */
    public function actionClassDetail($child = null)
    {
        return (new ApiResponse)->success(Utility::StudentClassDetails($child), ApiResponse::SUCCESSFUL);

    }


    /*public function actionUpdateSecurityQuestion()
    {

        if (!$model = SecurityQuestionAnswer::findOne(['user_id' => Yii::$app->user->id]))
            if (!$model) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
            }

        $model->answer = Yii::$app->request->post('answer');
        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Answer not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Answer updated');
    }*/

}