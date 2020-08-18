<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\Utility;
use app\modules\v2\models\HomeworkReport;
use app\modules\v2\models\Questions;
use app\modules\v2\models\Schools;
use app\modules\v2\models\UserModel;
use Yii;
use app\modules\v2\models\{Homeworks, Classes, ApiResponse};
use app\modules\v2\components\SharedConstant;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};

class ReportController extends ActiveController
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

    public function actionHomeworkSummary()
    {
        $id = Yii::$app->request->get('id');
        $data = Yii::$app->request->get('data');
        $model = new \yii\base\DynamicModel(compact('id', 'data'));
        $model->addRule(['id', 'data'], 'required')
            ->addRule(['id'], 'integer')
            ->addRule(['data'], 'string');

        $proceedStatus = false;
        $homework = Homeworks::find();
        if (Yii::$app->user->identity->type == 'school' && $homework->where(['id' => $id, 'school_id' => Schools::findOne(['id' => Utility::getSchoolAccess()])])->exists()) {
            $proceedStatus = true;
        } elseif (Yii::$app->user->identity->type == 'teacher' && $homework->where(['id' => $id, 'teacher_id' => Yii::$app->user->id])->exists()) {
            $proceedStatus = true;
        }


        if (!$proceedStatus)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not have access for this report');

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if ($data == 'student') {
            $model = UserModel::find()
                //->with(['assessmentTopicsPerformance'])
                ->innerJoin('quiz_summary qs', 'qs.student_id = user.id')
                ->where(['user.type' => SharedConstant::ACCOUNT_TYPE[3]])
                ->andWhere(['qs.homework_id' => $id, 'qs.submit' => SharedConstant::VALUE_ONE, 'qs.type' => 'homework'])
                ->all();
        } else if ($data == 'summary') {
            $model = HomeworkReport::find()->where(['id' => $id])->one();
        } else {
            $model = Questions::find()
                ->innerJoin('quiz_summary_details', 'quiz_summary_details.question_id = questions.id')
                ->innerJoin('quiz_summary', "quiz_summary.id = quiz_summary_details.quiz_id AND quiz_summary.type = 'homework'")
                ->innerJoin('homeworks h', "h.id = quiz_summary_details.homework_id")
                ->where(['quiz_summary_details.homework_id' => $id])
                ->all();
        }

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($data == 'summary' ? $model->getHomeworkSummary() : $model, ApiResponse::SUCCESSFUL, 'Record found');
    }
}
