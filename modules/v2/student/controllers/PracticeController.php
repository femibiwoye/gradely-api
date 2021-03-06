<?php

namespace app\modules\v2\student\controllers;

use app\modules\v2\components\SessionTermOnly;
use app\modules\v2\components\{SharedConstant, Utility};
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\HomeworkQuestions;
use app\modules\v2\models\Homeworks;
use app\modules\v2\models\Questions;
use app\modules\v2\models\QuizSummary;
use app\modules\v2\models\QuizSummaryDetails;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\TeacherClass;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use Yii;

class PracticeController extends Controller
{

    public $modelClass = 'app\modules\v2\models\PracticeTopics';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        //Add CORS Filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
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

    public function actionHomeworkInstruction($homework_id)
    {

        $studentClass = StudentSchool::findOne(['student_id' => \Yii::$app->user->id]);

        $homework = Homeworks::find()
            ->where(['id' => $homework_id, 'class_id' => $studentClass->class_id])
            ->one();

        if (!$homework) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No homework found!');
        }

        return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homework retrieved');

    }

    public function actionStartHomework()
    {

        $homework_id = \Yii::$app->request->post('homework_id');

        $studentClass = StudentSchool::findOne(['student_id' => \Yii::$app->user->id, 'status' => 1]);
        $class_id = $studentClass->class_id;

        $model = new \yii\base\DynamicModel(compact('class_id', 'homework_id'));
        $model->addRule(['homework_id'], 'required');
        $model->addRule(['homework_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id', 'class_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not validated');
        }

        $homework = Homeworks::find()
            ->where(['homeworks.id' => $homework_id, 'publish_status' => 1])->one();
        $currentTerm = SessionTermOnly::widget(['id' => $studentClass->school_id]);

        $homework_questions = HomeworkQuestions::find()->alias('hq')
            ->innerJoin('homeworks', 'homeworks.id = hq.homework_id')
            ->innerJoin('questions', 'questions.id = hq.question_id')
            ->andWhere(['hq.homework_id' => $homework_id])
            ->all();


        if (!$quizSummary = QuizSummary::find()->where(['homework_id' => $homework_id, 'student_id' => \Yii::$app->user->id])->one()) {
            $model = new QuizSummary();
            $model->attributes = \Yii::$app->request->post();
            $model->teacher_id = $homework->teacher_id;
            $model->student_id = \Yii::$app->user->id;
            $model->class_id = $studentClass->class_id;
            $model->subject_id = $homework->subject_id;
            $model->term = strtolower($currentTerm);
            $model->total_questions = count($homework_questions);
            $model->type = 'homework';
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not validated');
            }


            if (!$model->save()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No homework assigned for you');
            }

            return (new ApiResponse)->success(array_merge(ArrayHelper::toArray($homework), ['questions' => $homework->homeworkQuestions, 'quiz' => $model]), ApiResponse::SUCCESSFUL, 'Homework questions retrieved');

            //return message that homework is started
        } elseif ($quizSummary->submit == 0) {
            return (new ApiResponse)->success(array_merge(ArrayHelper::toArray($homework), ['questions' => $homework->homeworkQuestions, 'quiz' => $quizSummary]), ApiResponse::SUCCESSFUL, 'Homework Started');
        } else {
            //Quiz is either invalid or already submitted.
            return (new ApiResponse)->success($homework, ApiResponse::SUCCESSFUL, 'Homework Submitted');
        }
    }

    public function actionProcessHomework()
    {

        $attempts = \Yii::$app->request->post('attempts');
        $quiz_id = \Yii::$app->request->post('quiz_id');

        $student_id = \Yii::$app->user->id;

        $failedCount = 0;
        $correctCount = 0;
        $ungradedCount = 0;

        $model = new \yii\base\DynamicModel(compact('attempts', 'quiz_id', 'student_id'));
        $model->addRule(['attempts', 'quiz_id'], 'required')
            ->addRule(['quiz_id'], 'exist', ['targetClass' => QuizSummary::className(), 'targetAttribute' => ['quiz_id' => 'id', 'student_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homework not validated');
        }

        if (!is_array($attempts)) {
            //return error that questions is invalid
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question must be array');
        }

        //use transaction before saving;
        $dbtransaction = \Yii::$app->db->beginTransaction();
        try {
            $quizSummary = QuizSummary::findOne(['id' => $quiz_id, 'student_id' => \Yii::$app->user->id]);
            $hasEssay = false;
            foreach ($attempts as $question) {

                if (!isset($question['question']))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Attempt question data is not valid');

                if (!$questionModel = Questions::findOne(['id' => $question['question']]))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not valid');

                if ($questionModel->type != 'essay' && !isset($question['selected']))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Attempt selected data is not valid');

                if (!HomeworkQuestions::find()
                    ->where(['question_id' => $question['question'], 'homework_id' => $quizSummary->homework_id])->exists())
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, "Question '{$question['question']}' is invalid");

                if (in_array($quizSummary->type, ['multiple', 'bool']) && !in_array($question['selected'], SharedConstant::QUESTION_ACCEPTED_OPTIONS))
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, "Invalid option '{$question['selected']}' provided");

                $qsd = new QuizSummaryDetails();
                $qsd->quiz_id = $quizSummary->id;
                $qsd->question_id = $question['question'];


                $qsd->answer = $questionModel->answer;
                $qsd->topic_id = $questionModel->topic_id;
                $qsd->student_id = \Yii::$app->user->id;
                $qsd->homework_id = $quizSummary->homework_id;
                $qsd->max_score = $questionModel->score;
                if (isset($question['time_spent'])) {
                    $qsd->time_spent = $question['time_spent'];
                }

                if (in_array($questionModel->type, ['short', 'essay'])) {
                    $qsd->selected = $question['selected'];
                    if ($questionModel->type == 'short') {
                        $answers = json_decode($questionModel->answer);
                        $isScore = false;
                        foreach ($answers as $item) {
                            if (strtolower($item) == strtolower($question['selected'])) {
                                $correctCount = $correctCount + 1;
                                $qsd->is_correct = 1;
                                $isScore = true;
                                break;
                            }
                        }
                        if (!$isScore) {
                            $failedCount = $failedCount + 1;
                            $qsd->is_correct = 0;
                        }
                        $qsd->score = $questionModel->score;
                    } elseif ($questionModel->type == 'essay') {
                        $qsd->is_graded = 0;
                        $ungradedCount = $ungradedCount + 1;
                        $hasEssay = true;
                        if (isset($question['answer_attachment']))
                            $qsd->answer_attachment = $question['answer_attachment'];
                    }
                } elseif (in_array($questionModel->type, ['multiple', 'bool'])) {
                    $qsd->score = $questionModel->score;
                    $qsd->selected = strtoupper($question['selected']);
                    if ($question['selected'] == $questionModel->answer) {
                        $correctCount = $correctCount + 1;
                        $qsd->is_correct = 1;
                    } else {
                        $failedCount = $failedCount + 1;
                        $qsd->is_correct = 0;
                    }
                }

                if (!$qsd->save())
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'One or more attempt not saved');


            }

            $total_question = HomeworkQuestions::find()->where(['homework_id' => $quizSummary->homework_id])->count();
//            $maximumScore = HomeworkQuestions::find()->where(['homework_id' => $quizSummary->homework_id])->sum('max_score');

            if (!$total_question)
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'No question!');

            $quizSummary->failed = $failedCount;
            $quizSummary->correct = $correctCount;
            $quizSummary->ungraded = $ungradedCount;
            $quizSummary->total_questions = $total_question;
            $quizSummary->skipped = $total_question - ($correctCount + $failedCount + $ungradedCount);
            $quizSummary->submit = SharedConstant::VALUE_ONE;
            $quizSummary->submit_at = date('Y-m-d H:i:s');

            if ($hasEssay) {
                $quizSummary->computed = 0;
            } else {
                $quizSummary->computed = 1;
            }
            if (!$quizSummary->save())
                return (new ApiResponse)->error($quizSummary, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Score not saved');

            (new Utility)->generateRecommendation($quiz_id);

            $dbtransaction->commit();
            return (new ApiResponse)->success($quizSummary->computed == 1 ? $quizSummary : ['id' => $quizSummary->id, 'homework_id' => $quizSummary->homework_id, 'computed' => $quizSummary->computed], ApiResponse::SUCCESSFUL, 'Homework processing completed');
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            \Sentry\captureException($ex);
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Attempt was not successfully processed');
        }

    }

    public function actionProcessAttempt($quiz_id)
    {
        if (Yii::$app->user->identity->type != SharedConstant::ACCOUNT_TYPE[3]) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Permission not allowed');
        }

        $model = QuizSummary::findOne(['id' => $quiz_id, 'student_id' => Yii::$app->user->id]);
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model->attempt, ApiResponse::SUCCESSFUL, 'Record found');
    }
}
