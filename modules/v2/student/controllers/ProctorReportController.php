<?php

namespace app\modules\v2\student\controllers;

use Yii;
use yii\rest\ActiveController;
use app\modules\v2\models\{ApiResponse, ProctorReport, ProctorReportDetails, Homeworks, ProctorFeedback};
use app\modules\v2\components\{SharedConstant, CustomHttpBearerAuth};

class ProctorReportController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\ProctorReport';

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

    public function actionCreate()
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_STUDENT) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $model = new ProctorReport;
        $model->attributes = Yii::$app->request->post();
        $model->student_id = Yii::$app->user->id;
        $data = $this->proctorReport();
        if ($data) {
            return $this->addProctorReportDetails($data);
        }

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        $model->assessment_type = $this->getAssessmentType(Yii::$app->request->post('assessment_id'));
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Proctor Report insertion failed');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Proctor Report insertion passed');
    }

    private function getAssessmentType($assessment_id)
    {
        $assessment = Homeworks::findOne(['id' => $assessment_id]);
        return $assessment->type;
    }

    private function proctorReport()
    {
        return ProctorReport::findOne([
            'student_id' => Yii::$app->user->id,
            'assessment_id' => Yii::$app->request->post('assessment_id')
        ]);
    }

    private function addProctorReportDetails($data)
    {
        $model = new ProctorReportDetails;
        $model->attributes = Yii::$app->request->post();
        $model->report_id = $data->id;
        $model->user_id = $data->student_id;
        $model->assessment_id = $data->assessment_id;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Proctor Report Detail insertion failed');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Proctor Report Detail insertion passed');
    }

    public function actionProctorFeedback()
    {
        $model = new ProctorFeedback;
        $model->attributes = Yii::$app->request->post();
        $model->user_id = Yii::$app->user->id;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Feedback not inserted');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Feedback inserted');
    }
}