<?php

namespace app\modules\v2\exam\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use app\modules\v2\exam\components\ExamUtility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\exam\StudentExamConfig;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\UserModel;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
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
        $model = ExamType::find()
            ->where(['is_exam' => 1, 'class' => $category, 'slug' => ['bece', 'common-entrance','senior-waec']])
            ->select(['id', 'name', 'slug', 'title', 'description'])->all();
        return (new ApiResponse)->success($model);
    }

    public function actionSubject()
    {
        $category = ExamUtility::StudentClassCategory(Utility::ParentStudentChildClass(Utility::getParentChildID()));
        $model = Subjects::find()
            ->select(['subjects.id', 'subjects.name', 'subjects.slug','et.id as exam_id'])
            ->innerJoin('exam_subjects es','es.subject_id = subjects.id')
            ->innerJoin('exam_type et','et.id = es.exam_id')
            ->where(['subjects.school_id' => null, 'subjects.category' => ['all', $category]])
            ->andWhere(['et.is_exam' => 1, 'et.class' => $category, 'et.slug' => ['bece', 'common-entrance', 'senior-waec']])
            ->all();
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
        if (!in_array(Yii::$app->user->identity->type, SharedConstant::EXAM_MODE_USER_TYPE)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not have access');
        }

        $mode = Yii::$app->request->post('mode');
        $model = new \yii\base\DynamicModel(compact('mode'));
        $model->addRule(['mode'], 'required');
        $model->addRule(['mode'], 'in', ['range' => SharedConstant::EXAM_MODES]);
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

//        $studentID = Utility::getParentChildID();
        $studentID = Yii::$app->user->id;

        if (UserModel::updateAll(['mode' => $mode], ['id' => $studentID])) {
            return (new ApiResponse)->success(true, null, 'Mode updated');
        } else {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }
    }


    public function actionConfigureExam()
    {
        if (Yii::$app->user->identity->type != 'student' && Yii::$app->user->identity->type != 'parent') {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not have access');
        }

        $exam = Yii::$app->request->post('exam');
        $subject = Yii::$app->request->post('subject');
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

            $user = UserModel::findOne(['id' => Yii::$app->user->id]);
            $user->mode = SharedConstant::EXAM_MODES[1];
            $user->save();

            $dbtransaction->commit();
            return (new ApiResponse)->success(true, ApiResponse::SUCCESSFUL, 'Exam selected is configured');
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            return $this->addError('students', $e->getMessage());
        }
    }


    public function actionStudentExamTopics($subject_id)
    {
        $category = ExamUtility::StudentClassCategory(Utility::ParentStudentChildClass(Utility::getParentChildID()));
        $models = SubjectTopics::find()
            ->alias('st')
            ->select(['st.id'])
            ->leftJoin('questions q', 'q.topic_id = st.id')
            ->innerJoin('exam_type et', 'et.id = q.exam_type_id')
            ->where(['st.subject_id' => $subject_id])
            ->where(['q.category' => 'exam', 'et.is_exam' => 1, 'et.class' => $category, 'st.subject_id' => $subject_id])
            ->asArray()->all();

        return (new ApiResponse)->success(ArrayHelper::getColumn($models, 'id'));
    }

    /**
     * Student report filter options. For exam list
     * @return ApiResponse
     */
    public function actionExams()
    {
        $studentID = Utility::getParentChildID();

        $model = ExamType::find()
            ->select(['id', 'name', 'title', 'slug'])
            ->where(['id' => Utility::StudentExamSubjectID($studentID, 'exam_id')])
            ->all();
        return (new ApiResponse)->success($model);
    }

    /**
     * Student report filter options. For subject list
     * @return ApiResponse
     */
    public function actionSubjects()
    {
        $studentID = Utility::getParentChildID();

        $model = Subjects::find()
            ->select(['id', 'name', 'slug'])
            ->where(['id' => Utility::StudentExamSubjectID($studentID, 'subject_id')])
            ->all();
        return (new ApiResponse)->success($model);
    }

}