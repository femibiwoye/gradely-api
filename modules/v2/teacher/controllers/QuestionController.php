<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Classes;
use app\modules\v2\models\SchoolTopic;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\TeacherClass;
use Yii;
use app\modules\v2\models\{Homeworks, ApiResponse, HomeworkQuestions, Questions};
use app\modules\v2\teacher\models\{HomeworkQuestionsForm};
use app\modules\v2\components\SharedConstant;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};

class QuestionController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Questions';

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
                CustomHttpBearerAuth::className(),
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

    public function actionQuestions()
    {

        $homework_id = Yii::$app->request->get('homework_id');
        $teacher_id = Yii::$app->user->id;
        $form = new \yii\base\DynamicModel(compact('homework_id', 'teacher_id'));
        $form->addRule(['homework_id'], 'required');
        $form->addRule(['homework_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id', 'teacher_id' => 'teacher_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = Questions::find()
            ->innerJoin('homework_questions', "homework_questions.question_id = questions.id")
            ->where(['homework_questions.homework_id' => $homework_id, 'homework_questions.teacher_id' => $teacher_id])
            ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Question found');
    }

    public function actionHomeworkQuestions($homework_id)
    {
        $form = new HomeworkQuestionsForm;
        $form->attributes = Yii::$app->request->post();
        $form->homework_id = $homework_id;

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Record validation failed');
        }

        if (!$form->saveHomeworkQuestion()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not inserted');
        }

        return (new ApiResponse)->success($form->HomeworkQuestionModels, ApiResponse::SUCCESSFUL, 'Record inserted');
    }

    public function actionCreate($type)
    {
        $homework_id = Yii::$app->request->post('homework_id');
        $class_id = Yii::$app->request->post('class_id');
        $difficulty = Yii::$app->request->post('difficulty');
        $subject_id = Yii::$app->request->post('subject_id');
        $topic_id = Yii::$app->request->post('topic_id');
        $answer = Yii::$app->request->post('answer');
        $duration = Yii::$app->request->post('duration');
        $teacher_id = Yii::$app->user->id;
        $schoolID = Utility::getTeacherSchoolID($teacher_id, null);
        $model = new \yii\base\DynamicModel(compact('class_id', 'difficulty', 'subject_id', 'topic_id', 'answer', 'duration', 'teacher_id', 'homework_id'));
        $model->addRule(['homework_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id', 'teacher_id' => 'teacher_id']]);
        $model->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id' => 'class_id', 'teacher_id' => 'teacher_id']]);
        $model->addRule(['subject_id'], 'exist', ['targetClass' => Subjects::className(), 'targetAttribute' => ['subject_id' => 'id']]);
        $curriculumStatus = Utility::SchoolActiveCurriculum($schoolID, true);
        if (!$curriculumStatus) {
            $model->addRule(['topic_id'], 'exist', ['targetClass' => SubjectTopics::className(), 'targetAttribute' => ['topic_id' => 'id', 'subject_id' => 'subject_id']]);
        } else {
            $model->addRule(['topic_id'], 'exist', ['targetClass' => SchoolTopic::className(), 'targetAttribute' => ['topic_id' => 'id', 'subject_id' => 'subject_id']]);
        }
        $model->addRule(['difficulty'], 'in', ['range' => ['easy', 'medium', 'hard']]);
        if ($type == 'multiple')
            $model->addRule(['answer'], 'in', ['range' => ['A', 'B', 'C', 'D']]);
        elseif ($type == 'bool')
            $model->addRule(['answer'], 'in', ['range' => ['0', '1']]);
        $model->addRule(['duration', 'class_id'], 'integer');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
        }
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            $model = new Questions(['scenario' => 'create-' . $type]);
            $model->attributes = Yii::$app->request->post();
            $model->teacher_id = $teacher_id;
            // i added this later, meaning lots of questions had been created by teacher without adding school_id
            $model->school_id = $schoolID;

            if (Questions::find()->where(['question' => $model->question, 'answer' => $model->answer, 'teacher_id' => $model->teacher_id, 'type' => $type, 'option_a' => $model->option_a])->exists()) {
                return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'This is a duplicate question');
            }

            $model->type = $type;
            $model->category = 'homework';
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
            }

            if ($curriculumStatus) {
                $model->is_custom_topic = 1;
                $topic = SchoolTopic::findOne(['id' => $topic_id]);
                $examID = $topic->curriculum_id;
            } else {
                $topic = SubjectTopics::findOne(['id' => $topic_id]);
                $examID = $topic->exam_type_id;
            }
            $model->exam_type_id = $examID;
            $model->homework_id = $homework_id;
            $model->class_id = Classes::findOne(['id' => $class_id])->global_class_id;

            if (!$model->save()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not saved');
            }


            if (!empty($homework_id)) {
                $assignQuestion = new HomeworkQuestions();
                $assignQuestion->teacher_id = $teacher_id;
                $assignQuestion->homework_id = $homework_id;
                $assignQuestion->question_id = $model->id;
                $assignQuestion->duration = $model->duration;
                $assignQuestion->difficulty = $model->difficulty;
                if (!$assignQuestion->save()) {
                    return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not successfully added to homework');
                }
            }
            $dbtransaction->commit();
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            return false;
        }
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Question saved');
    }

    public function actionUploadQuestions()
    {
        $homework_id = Yii::$app->request->post('homework_id');
        $topic_id = Yii::$app->request->post('topic_id');
        $questions = Yii::$app->request->post('questions');
        $teacher_id = Yii::$app->user->id;
        $schoolID = Utility::getTeacherSchoolID($teacher_id, null);

        $model = new \yii\base\DynamicModel(compact('topic_id', 'teacher_id', 'homework_id', 'questions'));
        $model->addRule(['questions', 'homework_id', 'topic_id'], 'required');
        $model->addRule(['homework_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['homework_id' => 'id', 'teacher_id' => 'teacher_id']]);
        $curriculumStatus = Utility::SchoolActiveCurriculum($schoolID, true);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
        }

        $homework = Homeworks::findOne(['teacher_id' => $teacher_id, 'id' => $homework_id]);
        $subject_id = $homework->subject_id;
        $model = new \yii\base\DynamicModel(compact('topic_id', 'subject_id'));
        if (!$curriculumStatus) {
            $model->addRule(['topic_id'], 'exist', ['targetClass' => SubjectTopics::className(), 'targetAttribute' => ['topic_id' => 'id', 'subject_id' => 'subject_id']]);
        } else {
            $model->addRule(['topic_id'], 'exist', ['targetClass' => SchoolTopic::className(), 'targetAttribute' => ['topic_id' => 'id', 'subject_id' => 'subject_id']]);
        }
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
        }

        //try {
        $dbtransaction = Yii::$app->db->beginTransaction();
        foreach ($questions as $qIndex => $eachQuestion) {

            if (!isset($eachQuestion['type']) || !in_array($eachQuestion['type'], SharedConstant::QUESTION_FORMAT)) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Type is required or invalid');
            }

            $type = $eachQuestion['type'];
            $question = $eachQuestion['question'];
            $duration = $eachQuestion['duration'];
            $difficulty = $eachQuestion['difficulty'];
            $answer = $eachQuestion['answer'];
            $model = new \yii\base\DynamicModel(compact('topic_id', 'subject_id', 'type', 'duration', 'question', 'difficulty', 'answer'));
            $model->addRule(['topic_id', 'subject_id', 'type', 'duration', 'question', 'difficulty', 'answer'], 'required');
            if (!$curriculumStatus) {
                $model->addRule(['topic_id'], 'exist', ['targetClass' => SubjectTopics::className(), 'targetAttribute' => ['topic_id' => 'id', 'subject_id' => 'subject_id']]);
            } else {
                $model->addRule(['topic_id'], 'exist', ['targetClass' => SchoolTopic::className(), 'targetAttribute' => ['topic_id' => 'id', 'subject_id' => 'subject_id']]);
            }
            if ($eachQuestion['type'] == 'multiple')
                $model->addRule(['answer'], 'in', ['range' => ['A', 'B', 'C', 'D']]);
            elseif ($eachQuestion['type'] == 'bool')
                $model->addRule(['answer'], 'in', ['range' => ['0', '1']]);
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
            }

            $model = new Questions(['scenario' => 'create-' . $eachQuestion['type']]);
            $model->attributes = $eachQuestion;
            $model->teacher_id = $teacher_id;
            $model->school_id = $schoolID;
            $model->subject_id = $subject_id;
            $model->topic_id = $topic_id;
            $model->class_id = $homework->class_id;
            if (Questions::find()->where(['question' => $model->question, 'answer' => $model->answer, 'teacher_id' => $model->teacher_id, 'type' => $model->type, 'option_a' => $model->option_a])->exists()) {
                return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'This is a duplicate question');
            }

            $model->type = $eachQuestion['type'];
            $model->category = 'homework';
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
            }

            if ($curriculumStatus) {
                $model->is_custom_topic = 1;
                $topic = SchoolTopic::findOne(['id' => $topic_id]);
                $examID = $topic->curriculum_id;
            } else {
                $topic = SubjectTopics::findOne(['id' => $topic_id]);
                $examID = $topic->exam_type_id;
            }
            $model->exam_type_id = $examID;
            $model->homework_id = $homework_id;
            $model->class_id = Classes::findOne(['id' => $homework->class_id])->global_class_id;

            if (!$model->save()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not saved');
            }


            if (!empty($homework_id)) {
                $assignQuestion = new HomeworkQuestions();
                $assignQuestion->teacher_id = $teacher_id;
                $assignQuestion->homework_id = $homework_id;
                $assignQuestion->question_id = $model->id;
                $assignQuestion->duration = $model->duration;
                $assignQuestion->difficulty = $model->difficulty;
                if (!$assignQuestion->save()) {
                    return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not successfully added to homework');
                }
            }

        }

        $dbtransaction->commit();
//        } catch (\Exception $e) {
//            $dbtransaction->rollBack();
//            return false;
//        }
        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, ($qIndex + 1) . ' question(s) updated');

    }

    public function actionClassQuestions()
    {
        $class_id = Yii::$app->request->get('global_class_id');
        $subject_id = Yii::$app->request->get('subject_id');
        $topic_id = Yii::$app->request->get('topic_id');
        $form = new \yii\base\DynamicModel(compact('class_id', 'subject_id', 'topic_id'));
        $form->addRule(['class_id', 'subject_id', 'topic_id'], 'required');
        $form->addRule(['class_id', 'subject_id', 'topic_id'], 'exist', ['targetClass' => Questions::className(), 'targetAttribute' => ['class_id' => 'class_id', 'subject_id' => 'subject_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = Questions::find()
            ->where(['subject_id' => $subject_id, 'class_id' => $class_id])->andWhere(['<>', 'topic_id', 0]);

        $schoolID = Utility::getTeacherSchoolID(Yii::$app->user->id);
        $curriculumStatus = Utility::SchoolActiveCurriculum($schoolID, true);
        $custom_topic_id = null;
        if ($curriculumStatus) {
            $custom_topic = SchoolTopic::findOne(['id' => $topic_id, 'school_id' => $schoolID, 'subject_id' => $subject_id]);
            $custom_topic_id = ArrayHelper::getValue($custom_topic, 'topic_id', null);

            $model = $model->andWhere(['OR', ['topic_id' => $topic_id, 'is_custom_topic' => 1], ['topic_id' => $custom_topic_id, 'is_custom_topic' => 0]]);
        } else {
            $model = $model->andWhere(['topic_id' => $topic_id, 'is_custom_topic' => 0]);
        }

        if (!$model->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $provider = new \yii\data\ActiveDataProvider([
            'query' => $model,
            'pagination' => [
                'pageSize' => 20,
            ]
        ]);

        return (new ApiResponse)->success($provider->getModels(), ApiResponse::SUCCESSFUL, $provider->totalCount . ' record found', $provider);
    }

    public function actionView()
    {
        $question_id = Yii::$app->request->get('question_id');
        $teacher = Yii::$app->user->id;
        $form = new \yii\base\DynamicModel(compact('question_id', 'teacher'));
        $form->addRule(['question_id'], 'required');
        //$form->addRule(['teacher'], 'exist', ['targetClass' => HomeworkQuestions::className(), 'targetAttribute' => ['teacher' => 'teacher_id',
        //  'question_id' => 'question_id']]);
        $form->addRule(['question_id'], 'exist', ['targetClass' => Questions::className(), 'targetAttribute' => ['question_id' => 'id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = Questions::find()
            ->select([
                'questions.*',
                'st.topic',
                "(case when questions.teacher_id = $teacher then 1 else 0 end) as owner"
            ])
            ->leftJoin('subject_topics st', 'st.id = questions.topic_id')
            ->where(['questions.id' => $question_id])
            ->asArray()
            ->one();
        if ($model['is_custom_topic'] == 1) {
            $topic = SchoolTopic::findOne(['id' => $model['topic_id']]);
            $model = array_merge($model, ['topic' => isset($topic->topic) ? $topic->topic : null]);
        }

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        $model = Utility::FilterQuestionReturns($model);

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Question found');
    }

    public function actionDelete()
    {
        $homework_id = Yii::$app->request->get('homework_id');
        $question_id = Yii::$app->request->get('question_id');
        $teacher = Yii::$app->user->id;
        $form = new \yii\base\DynamicModel(compact('question_id', 'homework_id', 'teacher'));
        $form->addRule(['question_id', 'homework_id'], 'required');
        $form->addRule(['question_id'], 'exist', ['targetClass' => HomeworkQuestions::className(), 'targetAttribute' => ['teacher' => 'teacher_id',
            'question_id' => 'question_id', 'homework_id' => 'homework_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = HomeworkQuestions::findOne(['homework_id' => $homework_id, 'question_id' => $question_id]);
        if (!$model->delete()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not deleted');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Question deleted');
    }

    public function actionUpdate($id)
    {
        $difficulty = Yii::$app->request->post('difficulty');
        $topic_id = Yii::$app->request->post('topic_id');
        $answer = Yii::$app->request->post('answer');
        $duration = Yii::$app->request->post('duration');
        $question_id = Yii::$app->request->post('question_id');
        $teacher_id = Yii::$app->user->id;
        $model = new \yii\base\DynamicModel(compact('difficulty', 'topic_id', 'answer', 'duration', 'teacher_id', 'question_id'));
        $model->addRule(['question_id'], 'exist', ['targetClass' => Questions::className(), 'targetAttribute' => ['id' => 'question_id', 'teacher_id' => 'teacher_id']]);
        $model->addRule(['difficulty'], 'in', ['range' => ['easy', 'medium', 'hard']]);
        $model->addRule(['answer'], 'in', ['range' => ['A', 'B', 'C', 'D', '0', '1']]);
        $model->addRule(['duration'], 'integer');
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
        }

        $model = Questions::findOne(['id' => $id, 'teacher_id' => Yii::$app->user->id]);
        $model->scenario = 'update-' . $model->type;
        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not found or you have no edit privilege to this question');
        }

        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not validated');
        }

        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Question not updated');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Question updated');
    }
}
