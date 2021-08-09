<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;

use app\modules\v2\components\Utility;
use app\modules\v2\models\PracticeMaterial;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\models\QuizSummaryDetails;
use app\modules\v2\models\Remarks;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\{Homeworks, ApiResponse};

use app\modules\v2\student\models\ExamReport;
use app\modules\v2\student\models\HomeworkReport;
use app\modules\v2\student\models\StudentHomeworkReport;
use Yii;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use app\modules\v2\components\SharedConstant;


/**
 * Schools/Parent controller
 */
class HomeworkController extends ActiveController
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

    public function actionCompletedHomework($subject_id = null)
    {
        $student_id = Utility::getParentChildID();
        $models = StudentHomeworkReport::find()
            ->innerJoin('quiz_summary', 'quiz_summary.homework_id = homeworks.id')
            ->where(['quiz_summary.student_id' => $student_id, 'homeworks.type' => 'homework', 'quiz_summary.submit' => SharedConstant::VALUE_ONE])
            ->orderBy('quiz_summary.submit_at DESC')
            ->andWhere(['between', 'homeworks.created_at', Yii::$app->params['first_term_start'], Yii::$app->params['third_term_end']]);

        if ($subject_id)
            $models = $models->andWhere(['homeworks.subject_id' => $subject_id]);

        $models = $models->all();

        $missedModels = StudentHomeworkReport::find()
            ->innerJoin('student_school', "student_school.class_id = homeworks.class_id AND student_school.student_id=$student_id")
            ->leftJoin('quiz_summary qs', "qs.homework_id = homeworks.id AND qs.student_id = $student_id AND qs.submit = 1")
            ->leftjoin('homework_selected_student hss', "hss.homework_id = homeworks.id")
            ->where(['homeworks.type' => 'homework', 'homeworks.status' => SharedConstant::VALUE_ONE, 'homeworks.publish_status' => SharedConstant::VALUE_ONE])
            ->andWhere(['<', 'UNIX_TIMESTAMP(close_date)', time()])
            ->andWhere(['IS', 'qs.homework_id', null])
            ->andWhere(['between', 'homeworks.created_at', Yii::$app->params['first_term_start'], Yii::$app->params['third_term_end']])
            ->andWhere(['OR', ['homeworks.selected_student' => 1, 'hss.student_id' => $student_id], ['homeworks.selected_student' => 0]]);

            if ($subject_id)
                $models = $models->andWhere(['homeworks.subject_id' => $subject_id]);

        $missedModels = $missedModels->all();

//return $models;

        $models = array_merge($models, $missedModels);

        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new ArrayDataProvider([
            'allModels' => $models,
            'pagination' => [
                'pageSize' => 10,
                'validatePage' => false,
            ],
            'sort' => [

                'attributes' => [
                    'id'
                ],
                'defaultOrder' => ['id' => SORT_ASC]]
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found', $provider);
    }

    public function actionNewHomework($subject_id = null)
    {
        $student_id = Utility::getParentChildID();
        $models = $this->modelClass::find()
            ->innerJoin('student_school', "student_school.class_id = homeworks.class_id AND student_school.student_id=$student_id")
            ->leftJoin('quiz_summary qs', "qs.homework_id = homeworks.id AND qs.student_id = $student_id AND qs.submit = 1")
            ->leftjoin('homework_selected_student hss', "hss.homework_id = homeworks.id")
            ->where(['homeworks.type' => 'homework', 'homeworks.status' => SharedConstant::VALUE_ONE, 'homeworks.publish_status' => SharedConstant::VALUE_ONE])
            ->andWhere(['<', 'UNIX_TIMESTAMP(open_date)', time()])
            ->andWhere(['>', 'UNIX_TIMESTAMP(close_date)', time()])
            ->andWhere(['IS', 'qs.homework_id', null])
            ->andWhere(['OR', ['homeworks.selected_student' => 1, 'hss.student_id' => $student_id], ['homeworks.selected_student' => 0]])
            ->andWhere(['between', 'homeworks.created_at', Yii::$app->params['first_term_start'], Yii::$app->params['third_term_end']]);

        if ($subject_id)
            $models = $models->andWhere(['homeworks.subject_id' => $subject_id]);

        if (!$models) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new ActiveDataProvider([
            'query' => $models,
            'pagination' => [
                'pageSize' => 10,
                'validatePage' => false,
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found', $provider);
    }

    public function actionHomeworkScore($homework_id)
    {
        $student_id = Utility::getParentChildID();
        $homework = QuizSummary::find()->where(['student_id' => $student_id, 'homework_id' => $homework_id])->one();
        if (!$homework) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not found!');
        }
        if ($homework->submit != 1) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not scored');
        }

        return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homework scored successfully');
    }


    /**
     * Student report endpoint
     * Can be used for Homework, Exam, Quiz, Diagnostic, Practice.
     * @param $id
     * @return ApiResponse
     */
    public function actionHomeworkReport($id)
    {
        $student_id = Utility::getParentChildID();

        $mode = Utility::getChildMode($student_id);
        if ($mode == 'exam') {
            $model = ExamReport::findOne([
                'student_id' => $student_id,
                'homework_id' => $id, 'submit' => 1]);
        } else {

            $model = HomeworkReport::findOne([
                'student_id' => $student_id,
                'homework_id' => $id, 'submit' => 1]);
        }


        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework report not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Student report found');
    }

    public function actionHomeworkReviewQuestion($homework_id)
    {
        $summary_details = QuizSummaryDetails::find()->alias('qsd')
            ->innerJoin('quiz_summary', 'quiz_summary.id = qsd.quiz_id')
            ->innerJoin('homeworks', 'homeworks.id = quiz_summary.homework_id')
            ->innerJoin('questions', 'questions.id = qsd.question_id')
            ->andWhere([
                'quiz_summary.homework_id' => $homework_id,
                'homeworks.publish_status' => SharedConstant::VALUE_ONE,
                'homeworks.type' => 'homework',

            ])
            ->all();

        if (!$summary_details) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not found!');
        }

        return (new ApiResponse)->success($summary_details, ApiResponse::SUCCESSFUL, 'Questions succcessfully retrieved');
    }

    public function actionHomeworkReviewRecommendation($homework_id)
    {
        $topics = SubjectTopics::find()->alias('topic')
            ->innerJoin('quiz_summary_details qsd', 'topic.id = qsd.topic_id')
            ->innerJoin('questions q', 'q.topic_id = qsd.topic_id')
            ->andWhere(['qsd.homework_id' => $homework_id])
            ->all();

        if (!$topics) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No Recommendation found!');
        }

        return (new ApiResponse)->success($topics, ApiResponse::SUCCESSFUL, 'Homework recommendation retrieved');

    }


    public function actionVideos($child_id = null, $search = null)
    {
        $studentClassID = Utility::ParentStudentChildClass($child_id, 0);

        $model = PracticeMaterial::find()
            ->andWhere(['practice_material.filetype' => SharedConstant::FEED_TYPES[4], 'practice_material.type' => ['feed', 'practice'], 'feed.status' => 1, 'view_by' => ['all', 'class']]);
        $model = $model
            ->leftJoin('feed', 'feed.id = practice_material.practice_id AND practice_material.type = "feed"')
            ->leftJoin('homeworks', 'homeworks.id = practice_material.practice_id AND practice_material.type = "practice"')
            ->andWhere(['OR', ['feed.class_id' => $studentClassID], ['homeworks.class_id' => $studentClassID]]);

        if (!empty($search)) {
            $model = $model->
            andWhere(['OR',
                ['like', 'practice_material.title', '%' . $search . '%', false],
                ['like', 'filename', '%' . $search . '%', false],
                ['like', 'raw', '%' . $search . '%', false]
            ]);
        }
        $model = $model->orderBy(['created_at' => SORT_DESC])->groupBy('practice_material.id');

        $provider = new ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => 12,
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' record found', $provider);
    }

    public function actionNotes($child_id = null, $class_id = null, $term = null, $search = null)
    {
        if (!empty($class_id) && empty($child_id)) {
            $child_id = $class_id;
        }
        $studentClassID = Utility::ParentStudentChildClass($child_id, 0);

        $model = PracticeMaterial::find()
            ->andWhere(['practice_material.filetype' => 'document', 'practice_material.type' => ['feed', 'practice'], 'feed.status' => 1, 'view_by' => ['all', 'class']])
            ->groupBy('practice_material.id');
        $model = $model
            ->leftJoin('feed', 'feed.id = practice_material.practice_id AND practice_material.type = "feed"')
            ->leftJoin('homeworks', 'homeworks.id = practice_material.practice_id AND practice_material.type = "practice"')
            ->andWhere(['OR', ['feed.class_id' => $studentClassID], ['homeworks.class_id' => $studentClassID]])
            ->andWhere(['between', 'practice_material.created_at', Yii::$app->params['first_term_start'], Yii::$app->params['third_term_end']]);

        if ($search) {
            $model = $model->
            andWhere(['OR', ['like', 'practice_material.title', '%' . $search . '%', false], ['like', 'filename', '%' . $search . '%', false], ['like', 'raw', '%' . $search . '%', false]]);
        }
        $model = $model->orderBy(['created_at' => SORT_DESC])->all();

        $uniqueData = ArrayHelper::getColumn($model, 'uploadTermWeek');
        $dates = array_unique($uniqueData);
        $bothFinal = [];
        foreach ($dates as $k => $date) {
            $tempData = [];
            foreach ($model as $y => $element) {
                if ($date == $element['uploadTermWeek']) {
                    $tempData[] = $element;
                }
            }
            $bothFinal[] = ['date' => $date, 'data' => $tempData];
        }


        $provider = new ArrayDataProvider([
            'models' => $bothFinal,
            'pagination' => [
                'pageSize' => 12,
                'validatePage' => false,
            ],
            'sort' => [
                'attributes' => ['updated_at'],
            ],
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, 'Record found', $provider);
    }
}