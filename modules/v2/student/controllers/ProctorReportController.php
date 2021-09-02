<?php

namespace app\modules\v2\student\controllers;

use Yii;
use yii\helpers\ArrayHelper;
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

    public function actionCreate($bulk = 0)
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_STUDENT) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }


        if ($bulk == 1) {
            $post = Yii::$app->request->post();
            $model = new ProctorReportDetails;
            $model->attributes = $post[0];
            $model->user_id = Yii::$app->user->id;
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
            }
            $data = $this->proctorReport($model->assessment_id, $model->integrity);
            $inserted = 0;
            if ($data) {
                foreach ($post as $item) {
                    $item = (object)$item;
                    $data = $this->proctorReport($item->assessment_id, $item->integrity);
                    if ($data) {
                        if ($reps = $this->addProctorReportDetails($data, $item))
                            $inserted++;
                        else {
                            return (new ApiResponse)->error($reps, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
                        }
                    }
                }
            }
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, $inserted . ' proctor inserted!');
        } else {
            //Validate
            $model = new ProctorReportDetails;
            $model->attributes = Yii::$app->request->post();
            $model->user_id = Yii::$app->user->id;
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
            }

            $data = $this->proctorReport($model->assessment_id, $model->integrity);
            if ($data) {
                if ($reps = $this->addProctorReportDetails($data, Yii::$app->request->post()))
                    return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Proctor update inserted!');
                else {
                    return (new ApiResponse)->error($reps, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
                }
            }
        }

        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Error processing');
    }

    private function getAssessmentType($assessment_id)
    {
        $assessment = Homeworks::findOne(['id' => $assessment_id]);
        return $assessment->type;
    }

    private function proctorReport($assessment_id, $integrity)
    {
        $model = ProctorReport::findOne([
            'student_id' => Yii::$app->user->id,
            'assessment_id' => $assessment_id
        ]);
        if (!$model) {
            $model = new ProctorReport();
            $model->student_id = Yii::$app->user->id;
            $model->assessment_type = $this->getAssessmentType($assessment_id);
            $model->assessment_id = $assessment_id;
            $model->integrity = $integrity;
            $model->save();
        }
        return $model;
    }

    private function addProctorReportDetails($data, $detailModel)
    {
        $model = new ProctorReportDetails;
        $model->attributes = (array)$detailModel;
        $model->report_id = $data->id;
        $model->user_id = $data->student_id;
        $model->assessment_id = $data->assessment_id;
        if (!$model->validate()) {
            return false;
        }

        if (!$model->save()) {
            return false;
        }
        return $model;
    }

    public function actionProctorFeedback()
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_TEACHER) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $model = new ProctorFeedback;
        $model->attributes = Yii::$app->request->post();
        $model->user_id = Yii::$app->user->id;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Feedback not saved');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Feedback saved');
    }
}