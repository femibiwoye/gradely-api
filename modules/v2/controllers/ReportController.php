<?php

namespace app\modules\v2\controllers;

use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\Utility;
use app\modules\v2\models\HomeworkReport;
use app\modules\v2\models\Parents;
use app\modules\v2\models\Questions;
use app\modules\v2\models\Remarks;
use app\modules\v2\models\ReportError;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use Yii;
use app\modules\v2\models\{Homeworks, Classes, ApiResponse, StudentMastery, StudentAdditiionalTopics, StudentTopicPerformance};
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
        if (Yii::$app->user->identity->type == 'school' && $homework->where(['id' => $id, 'school_id' => Utility::getSchoolAccess()])->exists()) {
            $proceedStatus = true;
        } elseif (Yii::$app->user->identity->type == 'teacher' && $homework->where([
                'id' => $id,
                'teacher_id' => Yii::$app->user->id
            ])->exists()) {
            $proceedStatus = true;
        }


        if (!$proceedStatus)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not have access for this report');

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if ($data == 'student') {
            $model = UserModel::find()
                ->with(['proctor'])
                ->innerJoin('quiz_summary qs', 'qs.student_id = user.id')
                ->innerJoin('student_school ss', 'ss.class_id = qs.class_id AND ss.student_id = qs.student_id')
                ->where(['user.type' => SharedConstant::ACCOUNT_TYPE[3]])
                ->andWhere([
                    'qs.homework_id' => $id,
                    'qs.submit' => SharedConstant::VALUE_ONE,
                    'qs.type' => 'homework'])
                ->all();
        } else if ($data == 'summary') {
            $model = HomeworkReport::find()->where(['id' => $id])->one();
        } else {
            $model = Questions::find()
                ->leftJoin('quiz_summary_details', 'quiz_summary_details.question_id = questions.id')
                ->leftJoin('quiz_summary', "quiz_summary.id = quiz_summary_details.quiz_id AND quiz_summary.type = 'homework'")
                ->innerJoin('homework_questions', 'homework_questions.question_id = questions.id')
                ->innerJoin('homeworks h', "h.id = homework_questions.homework_id")
                ->where(['h.id' => $id])
                //->where(['quiz_summary_details.homework_id' => $id])
                ->groupBy('questions.id')
                ->all();
        }

        if (!$model) {
            return (new ApiResponse)->error([], ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($data == 'summary' ? $model->getHomeworkSummary() : $model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionReportError($type = null)
    {
        $model = new ReportError(['scenario' => 'question-report']);
        $model->attributes = Yii::$app->request->post();
        $model->user_id = Yii::$app->user->identity->id;
        $model->type = $type;
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Data not validated');
        }

        if (!$model->save(false)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not inserted');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record inserted');
    }

    public function actionGetRemarks($type, $id)
    {

        if (Yii::$app->user->identity->type == 'parent') {

            if (!Parents::findOne(['student_id' => $id, 'parent_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE]))
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child not found');
        }

        $model = Remarks::find()
            ->where(['receiver_id' => $id, 'type' => $type])
            ->all();

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, count($model) . ' remarks found');
    }

    public function actionCreateRemarks($type, $id)
    {
        $remark = Yii::$app->request->post('remark');
        $form = new \yii\base\DynamicModel(compact('remark'));
        $form->addRule(['remark'], 'required');

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if (!$this->checkUserAccess($type, $id)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid access!');
        }

        if (Yii::$app->user->identity->type == 'parent') {
            if (!Parents::findOne(['student_id' => $id, 'parent_id' => Yii::$app->user->id, 'status' => SharedConstant::VALUE_ONE]))
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Child not found');
        }


        $model = new Remarks();
        $model->type = $type;
        $model->creator_id = Yii::$app->user->id;
        $model->receiver_id = $id;
        $model->remark = $remark;
        if ($model->save())
            return (new ApiResponse)->success($model);

        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not send remark');
    }

    public function checkUserAccess($type, $id)
    {
        if ($type == 'student') {
            if (Yii::$app->user->identity->type == 'teacher') {
                $teacher_classes = TeacherClass::find()->where(['teacher_id' => Yii::$app->user->id, 'status' => 1])->all();
                foreach ($teacher_classes as $teacher_class) {
                    if (StudentSchool::find()->where(['class_id' => $teacher_class->class_id])->andWhere(['student_id' => $id])->exists()) {
                        return true;
                    }
                }
            } elseif (Yii::$app->user->identity->type == 'student') {
                if (Yii::$app->user->id == $id)
                    return true;
            } elseif (Yii::$app->user->identity->type == 'parent') {
                if (Parents::find()->where(['parent_id' => Yii::$app->user->id, 'student_id' => $id])->exists())
                    return true;
            }
        } elseif ($type == 'homework') {
            if (!$model = Homeworks::find()->where(['id' => $id])->one())
                return false;
            if (Yii::$app->user->identity->type == 'teacher') {
                if ($model->teacher_id == Yii::$app->user->id)
                    return true;
            } elseif (Yii::$app->user->identity->type == 'student') {
                if (StudentSchool::find()->where(['student_id' => Yii::$app->user->id, 'class_id' => $model->class_id])->exists())
                    return true;
            } elseif (Yii::$app->user->identity->type == 'parent') {
                $studentsID = ArrayHelper::getColumn(Parents::find()->where(['parent_id' => Yii::$app->user->id])->all(), 'student_id');
                if (StudentSchool::find()->where(['student_id' => $studentsID, 'class_id' => $model->class_id])->exists())
                    return true;
            }
        }

        return false;
    }

    public function actionSingleMasteryReport()
    {
        if (Yii::$app->user->identity->type == 'school' || Yii::$app->user->identity->type == 'teacher') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $model = new StudentMastery;
        $model->student_id = Yii::$app->user->identity->type == 'student' ? Yii::$app->user->id : Yii::$app->request->get('student_id');
        $model->class_id = Yii::$app->request->get('class_id');
        $model->subject_id = Yii::$app->request->get('subject_id');
        if (!$model->getData()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model->getData(), ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionMasteryReport()
    {
        $user = Yii::$app->user->identity;
        if ($user->type != 'student' && $user->type != 'parent') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $model = new StudentMastery;
        $model->student_id = Yii::$app->user->identity->type == 'student' ? Yii::$app->user->id : Yii::$app->request->get('student_id');
        $model->class_id = Yii::$app->request->get('class_id');
        $model->subject_id = Yii::$app->request->get('subject_id');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        if (!$model->getGlobalData()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model->getGlobalData(), ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionTopicPerformance()
    {
        $model = new StudentTopicPerformance;
        $model->term = Yii::$app->request->get('term');
        $model->class = Yii::$app->request->get('class');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }

        return (new ApiResponse)->success($model->getPerformance(), ApiResponse::SUCCESSFUL, 'Topic Performance Record found');
    }
}
