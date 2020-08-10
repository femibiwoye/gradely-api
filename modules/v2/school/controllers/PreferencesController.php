<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\InviteLog;
use app\modules\v2\models\SchoolAdmin;
use app\modules\v2\models\SchoolCurriculum;
use app\modules\v2\models\SchoolRole;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolSubject;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\Timezone;
use app\modules\v2\school\models\PreferencesForm;
use Yii;
use app\modules\v2\models\{User, ApiResponse, UserPreference};
use app\modules\v2\components\{SharedConstant};
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


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
        unset($actions['create']);
        unset($actions['update']);
        unset($actions['delete']);
        unset($actions['index']);
        unset($actions['view']);
        return $actions;
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
            ->select([
                's.school_id',
                'subjects.id',
                'subjects.slug',
                'subjects.name',
                'subjects.description',
                'count(d.class_id) class_subject_count',
                //'count(c.id) teacher_class_count'
                new Expression('CASE WHEN h.subject_id IS NULL THEN 1 ELSE 0 END as can_delete')
            ])
            ->where(['s.school_id' => $school->id, 's.status' => 1])
            //->leftJoin('teacher_class_subjects c', "c.subject_id = s.subject_id AND c.school_id = '$school->id'")
            ->leftJoin('class_subjects d', "d.subject_id = s.subject_id AND d.school_id = '$school->id'")
            ->innerJoin('subjects', "subjects.id = s.subject_id")
            ->leftJoin('homeworks h', "h.subject_id = s.subject_id AND h.school_id = s.school_id")
            ->groupBy(['s.subject_id'])
            ->asArray();

        if (!$mySubjects->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No subject available!');
        }
        return (new ApiResponse)->success($mySubjects->all(), ApiResponse::SUCCESSFUL, $mySubjects->count() . ' subjects found');
    }

    public function actionAddSubject()
    {
        $form = new PreferencesForm(['scenario' => 'add-subject']);
        $form->attributes = Yii::$app->request->post();
        $classes = Yii::$app->request->post('classes');
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        if (!$model = $form->addSubject($school, $classes)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Subject not created');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionUsers()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $mySubjects = SchoolAdmin::find()
            ->alias('s')
            ->select([
                //'s.id',
                's.school_id',
                's.user_id',
                's.level',
                's.status',
                'u.firstname',
                'u.lastname',
                'u.email',
                'u.image',
                'r.title',
                "'0' AS `owner`",
            ])
            ->where(['s.school_id' => $school->id])
            ->innerJoin('user u', "u.id = s.user_id")
            ->innerJoin('school_role r', "r.slug = s.level")
            ->asArray()->all();

        $schoolOwner = [
            'school_id' => "$school->id",
            'user_id' => "$school->user_id",
            'level' => 'owner',
            'status' => "1",
            'firstname' => $school->user->firstname,
            'lastname' => $school->user->lastname,
            'email' => $school->user->email,
            'image' => $school->user->image,
            'title' => 'Owner',
            'owner' => '1',
        ];


        //$all = $mySubjects->all();
        array_unshift($mySubjects, $schoolOwner);

        if (!$mySubjects) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No user available!');
        }
        return (new ApiResponse)->success($mySubjects, ApiResponse::SUCCESSFUL);
    }

    public function actionChangeUserRole()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        if (Utility::getSchoolRole($school) != SharedConstant::SCHOOL_OWNER_ROLE)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You cannot perform this action');

        $form = new PreferencesForm(['scenario' => 'update-user-role']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = SchoolAdmin::find()->where(['school_id' => $school->id, 'user_id' => $form->user_id]);
        if (!$model->exists() || !SchoolRole::find()->where(['slug' => $form->role, 'status' => 1])->exists())
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User either does not exist or role is not valid');

        $model = $model->one();
        $model->level = $form->role;
        $model->save();
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'User has been disabled!');

    }

    public function actionDeactivateUser()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        if (Utility::getSchoolRole($school) != SharedConstant::SCHOOL_OWNER_ROLE)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You cannot perform this action');

        $form = new PreferencesForm(['scenario' => 'update-user']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = SchoolAdmin::find()->where(['school_id' => $school->id, 'user_id' => $form->user_id, 'status' => 1]);
        if (!$model->exists())
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User either does not exist or already disabled');

        $model = $model->one();
        $model->status = 0;
        $model->save();
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'User has been disabled!');

    }

    public function actionActivateUser()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        if (Utility::getSchoolRole($school) != SharedConstant::SCHOOL_OWNER_ROLE)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You cannot perform this action');

        $form = new PreferencesForm(['scenario' => 'update-user']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = SchoolAdmin::find()->where(['school_id' => $school->id, 'user_id' => $form->user_id, 'status' => 0]);
        if (!$model->exists())
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User either does not exist or already active');

        $model = $model->one();
        $model->status = 1;
        $model->save();
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'User has been enabled!');

    }

    public function actionRemoveUser()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        if (Utility::getSchoolRole($school) != SharedConstant::SCHOOL_OWNER_ROLE)
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You cannot perform this action');

        $form = new PreferencesForm(['scenario' => 'update-user']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = SchoolAdmin::find()->where(['school_id' => $school->id, 'user_id' => $form->user_id]);
        if (!$model->exists())
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User does not exist');

        $model->one()->delete();

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'User has been removed!');

    }


    public function actionTimezone()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $form = new PreferencesForm(['scenario' => 'update-timezone']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!Timezone::find()->where(['name' => $form->timezone])->exists()) {
            $form->addError('timezone', 'Unknown timezone is provided');
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }
        $school->timezone = $form->timezone;
        $school->save();
        return (new ApiResponse)->success($school, ApiResponse::SUCCESSFUL, 'Timezone updated');

    }

    public function actionSlug()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $form = new PreferencesForm(['scenario' => 'update-slug']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $school->slug = $form->slug;
        $school->save();
        return (new ApiResponse)->success($school, ApiResponse::SUCCESSFUL, 'Address updated');

    }

    public function actionPendingUser()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $model = InviteLog::find()->where(['status' => 0, 'sender_id' => $school->id, 'receiver_type' => 'school', 'sender_type' => 'school'])->all();
        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL);
    }

}

