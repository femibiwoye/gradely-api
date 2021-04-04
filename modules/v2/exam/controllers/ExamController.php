<?php

namespace app\modules\v2\exam\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use app\modules\v2\exam\components\ExamUtility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\UserModel;
use app\modules\v2\sms\models\StudentExamConfig;
use Yii;
use yii\filters\AccessControl;
use yii\rest\ActiveController;


/**
 * Schools/Parent controller
 */
class ExamController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Homeworks';

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
                        return in_array(Yii::$app->user->identity->type, SharedConstant::EXAM_MODE_USER_TYPE);
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

    public function actionList()
    {
        $category = ExamUtility::StudentClassCategory(Utility::ParentStudentChildClass(Utility::getParentChildID()));
        $model = ExamType::find()->where(['is_exam' => 1, 'class' => $category])->select(['name', 'slug', 'title', 'description'])->all();
        return (new ApiResponse)->success($model);
    }

    public function actionSubject()
    {
        $category = ExamUtility::StudentClassCategory(Utility::ParentStudentChildClass(Utility::getParentChildID()));
        $model = Subjects::find()->where(['school_id' => null, 'category' => ['all', $category]])->all();
        return (new ApiResponse)->success($model);
    }

    public function actionActiveMode()
    {
        $mode = UserModel::findOne(Yii::$app->user->id)->mode;

        return (new ApiResponse)->success($mode);
    }

    /**
     * Update catchup mode.
     * @return ApiResponse
     */
    public function actionUpdateMode()
    {
        $mode = Yii::$app->request->post('mode');
        $model = new \yii\base\DynamicModel(compact('mode'));
        $model->addRule(['mode'], 'required');
        $model->addRule(['mode'], 'in', ['range' => SharedConstant::EXAM_MODES]);
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $studentID = Utility::getParentChildID();

        if (UserModel::updateAll(['mode' => $mode], ['id' => $studentID])) {
            return (new ApiResponse)->success(true, null, 'Mode updated');
        } else {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }
    }


    public function actionConfigureExam()
    {
        $exam = Yii::$app->request->post('exams');
        $subject = Yii::$app->request->post('subjects');
        $model = new \yii\base\DynamicModel(compact('exam', 'subject'));
        $model->addRule(['exam', 'subject'], 'required');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!is_array($exam) || !is_array($subject)) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Exam and subject must be an array');
        }

        $studentID = Utility::getParentChildID();

        $category = ExamUtility::StudentClassCategory(Utility::ParentStudentChildClass($studentID));
        $countExam = ExamType::find()->where(['id' => $exam, 'is_exam' => 1, 'class' => $category])->count();
        if (count($exam) > $countExam) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'One or more of your exam is invalid');
        }

        $countSubject = Subjects::find()->where(['school_id' => null, 'category' => ['all', $category]])->count();
        if (count($subject) > $countSubject) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'One or more of your subject is invalid');
        }
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {

//            if (StudentExamConfig::find()->where(['subject_id' => $studentID])->exists()) {
//                StudentExamConfig::deleteAll(['student_id' => $studentID]);
//            }
//
            foreach ($exam as $item) {
                foreach ($subject as $row) {
                    if (StudentExamConfig::find()->where(['exam_id' => $item, 'subject_id' => $row, 'status' => 1, 'student_id' => $studentID])->exists()) {
                        continue;
                    }
                    $model = new StudentExamConfig();
                    $model->student_id = $studentID;
                    $model->exam_id = $item;
                    $model->subject_id = $row;
                    $model->status = 1;
                    $model->save();
                }
            }
            $dbtransaction->commit();
            return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Question saved');
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            return $this->addError('students', $e->getMessage());
        }
    }


}