<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\{Utility, SharedConstant};
use app\modules\v2\models\LearningArea;
use app\modules\v2\models\{Schools, StudentSchool, Classes, ApiResponse, TeacherClass, User, Homeworks};
use app\modules\v2\models\SchoolTopic;
use app\modules\v2\models\SubjectTopics;
use app\modules\v2\school\models\PreferencesForm;
use Yii;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;


/**
 * Schools/Parent controller
 */
class CurriculumController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Classes';

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
                        return Yii::$app->user->identity->type == 'school';
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

    /**
     * Fetch and Search for topics
     *
     * @param $subject
     * @param $school_class
     * @param $global_class
     * @param null $term
     * @param null $search
     * @return ApiResponse
     */
    public function actionTopics($subject, $school_class, $global_class, $term = null, $search = null)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        if (SchoolTopic::find()->where(['school_id' => $school])->exists() && empty($term)) {
            $models = SchoolTopic::find()
                ->select([
                    'id',
                    'topic_id',
                    'topic',
                    new Expression('1 is_custom'),
                    'week',
                    'term',
                    'curriculum_id AS curriculum',
                    new Expression("null AS image"),
                    'position'
                ])
                //->with('learningArea')
                ->where(['school_id' => $school->id, 'class_id' => $school_class, 'subject_id' => $subject])->orderBy(['position'=>SORT_ASC,'week'=>SORT_ASC])->asArray();
        } else {
            $models = SubjectTopics::find()
                ->select([
                    'id',
                    new Expression('id topic_id'),
                    'topic',
                    new Expression('0 is_custom'),
                    'week_number AS week',
                    'term',
                    'exam_type_id AS curriculum',
                    'image',
                    new Expression("null AS position"),
                ])
                ->with(['learningArea'])
                ->where(['status' => 1, 'class_id' => $global_class, 'subject_id' => $subject])->asArray();
            if (!empty($term)) {
                $models = $models->with(['isReferenced'])->andWhere(['AND', ['term' => $term], ['like', 'topic', '%' . $search . '%', false]])->all();
                return (new ApiResponse)->success($models);
            }
        }


        $bothFinal = [];
        foreach (['first', 'second', 'third'] as $k => $term) {
            $tempData = [];
            foreach ($models->all() as $y => $element) {
                if ($term == $element['term']) {
                    $tempData[] = $element;
                }
            }
            $bothFinal[] = ['term' => $term, 'topics' => $tempData];
        }
        return (new ApiResponse)->success($bothFinal);

    }

    /**
     * Create new topic.
     * If school curriculum is using gradely default, it will create a new custom curriculum for them.
     * @return ApiResponse
     */
    public function actionCreateTopic()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]); //Get school details
        $preferenceForm = new PreferencesForm();
        $preferenceForm->EnsureAndCreateCurriculum($school); // Check curriculum exist, else create curriculum
        $schoolCurriculum = Utility::SchoolActiveCurriculum($school->id); //Get school active curriculum

        SchoolTopic::SchoolReplicateTopic($school->id, $schoolCurriculum); //Generate all topics if it does not exist
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            $model = new SchoolTopic();
            $learning = Yii::$app->request->post('learning');
            $model->attributes = Yii::$app->request->post();
            $model->school_id = $school->id;
            $model->curriculum_id = $schoolCurriculum;
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR);
            }
            if (!$model->save())
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
            if (!empty($learning) && is_array($learning)) {
                foreach ($learning as $item) {
                    $learningArea = new LearningArea();
                    $learningArea->topic_id = $model->id;
                    $learningArea->is_school = 1;
                    $learningArea->topic = $item['topic'];
                    $learningArea->week = $item['week'];
                    $learningArea->class_id = $model->class_id;
                    $learningArea->subject_id = $model->subject_id;
                    $learningArea->save();
                }
            }
            $dbtransaction->commit();
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            return (new ApiResponse)->error($e, ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        return (new ApiResponse)->success(array_merge($model, ['learning' => $model]));
    }

    public function actionOrderTopic($is_custom, $subject, $school_class)
    {
        $topics = Yii::$app->request->post('topics');
        if (empty($topics) || !is_array($topics)) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Topics cannot be empty and must be an objects array');
        }
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]); //Get school details
        $preferenceForm = new PreferencesForm();
        $preferenceForm->EnsureAndCreateCurriculum($school); // Check curriculum exist, else create curriculum
        $schoolCurriculum = Utility::SchoolActiveCurriculum($school->id); //Get school active curriculum
        SchoolTopic::SchoolReplicateTopic($school->id, $schoolCurriculum); //Generate all topics if it does not exist
        if (SchoolTopic::find()->where(['school_id' => $school])->exists() && $is_custom == 0)
            $field = 'topic_id';
        else
            $field = 'id';
        $terms = ['first', 'second', 'third'];
//        $dbtransaction = Yii::$app->db->beginTransaction();
//        try {
        foreach ($terms as $term) {
            if (!isset($topics[$term]))
                continue;
            foreach ($topics[$term] as $order => $topic) {
                $model = SchoolTopic::findOne([$field => $topic['id'], 'class_id' => $school_class, 'subject_id' => $subject]);
                $model->term = $term;
                $model->position = $order + 1;
                $model->save();
            }
        }
//            $dbtransaction->commit();
//        } catch (\Exception $e) {
//            $dbtransaction->rollBack();
//            return (new ApiResponse)->error($e, ApiResponse::UNABLE_TO_PERFORM_ACTION);
//        }
        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Updated');
    }

    /**
     * This deletes a topics from custom curriculum
     * @param $id
     * @param $is_custom
     * @return ApiResponse
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDeleteTopic($id, $is_custom)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]); //Get school details
        $preferenceForm = new PreferencesForm();
        $preferenceForm->EnsureAndCreateCurriculum($school); // Check curriculum exist, else create curriculum
        $schoolCurriculum = Utility::SchoolActiveCurriculum($school->id); //Get school active curriculum
        if (SchoolTopic::find()->where(['school_id' => $school])->exists() && $is_custom == 0) {
            $field = 'reference_id';
        } else {
            $field = 'id';
        }
        SchoolTopic::SchoolReplicateTopic($school->id, $schoolCurriculum); //Generate all topics if it does not exist
        $model = SchoolTopic::findOne([$field => $id]);
        if (!$model->delete())
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Deleted');
    }

}