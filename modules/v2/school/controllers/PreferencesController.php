<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\Utility;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\SchoolCurriculum;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolSubject;
use app\modules\v2\models\Subjects;
use app\modules\v2\school\models\PreferencesForm;
use Yii;
use app\modules\v2\models\{User, ApiResponse, UserPreference};
use app\modules\v2\components\{SharedConstant};
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};


/**
 * Auth controller
 */
class PreferencesController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\UserModel';

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
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
    }

    public function actionCurriculum()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $examType = ExamType::find()
            ->alias('e')
            ->select(['e.*', new Expression('CASE WHEN s.curriculum_id IS NULL THEN 0 ELSE 1 END as active')])
            ->leftJoin('school_curriculum s', "s.curriculum_id = e.id AND s.school_id = $school->id")
            ->where(['OR', ['e.school_id' => null], ['e.school_id' => $school->id]]);

        return (new ApiResponse)->success($examType->asArray()->all(), ApiResponse::SUCCESSFUL, $examType->count() . ' classes found');
    }

    public function actionNewCurriculum()
    {
        $form = new PreferencesForm(['scenario' => 'curriculum-request']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        if (!$model = $form->addCurriculum($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class is not updated');
        }

        return (new ApiResponse)->success($model);
    }

    /**
     * This update school curriculum onChecked.
     * When you check, it will be added to school curriculum list
     * When you uncheck, it will remove the curriculum from the list.
     *
     * @return ApiResponse
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionUpdateCurriculum()
    {
        $form = new PreferencesForm(['scenario' => 'update-curriculum']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $curriculum = SchoolCurriculum::find()->where(['school_id' => $school->id, 'curriculum_id' => $form->curriculum_id]);
        if ($curriculum->exists()) {
            $curriculum->one()->delete();
            return (new ApiResponse)->success(false, null, 'Curriculum removed!');
        } else {
            if (!ExamType::find()->where(['id' => $form->curriculum_id])->exists()) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid curriculum!');
            }
            $new = new SchoolCurriculum();
            $new->curriculum_id = $form->curriculum_id;
            $new->school_id = $school->id;
            $new->save();
            return (new ApiResponse)->success(true, null, 'Curriculum added!');
        }
    }

    /**
     * Lists of subjects in the school
     *
     * @return ApiResponse
     */
    public function actionSubjects()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $mySubjects = SchoolSubject::find()
            ->alias('s')
            ->select(['subjects.*', 'count(c.subject_id) classes_count'])
            ->where(['s.school_id' => $school->id, 's.status' => 1])
            ->leftJoin('teacher_class_subjects c', "c.subject_id = s.subject_id AND c.school_id = $school->id")
            ->innerJoin('subjects', "subjects.id = s.subject_id")
            ->groupBy(['s.subject_id', 'c.subject_id'])
            ->asArray();

        if (!$mySubjects->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No subject available!');
        }
        return (new ApiResponse)->success($mySubjects->all(), ApiResponse::SUCCESSFUL, $mySubjects->count() . ' subjects found');
    }



}

