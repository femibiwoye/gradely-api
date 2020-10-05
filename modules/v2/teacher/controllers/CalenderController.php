<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\components\Utility;
use app\modules\v2\models\Schools;
use Yii;
use yii\db\Expression;
use app\modules\v2\models\{ApiResponse, Homeworks, TutorSession, SchoolTeachers};
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\components\SharedConstant;

/**
 * ClassController implements the CRUD actions for Classes model.
 */
class CalenderController extends ActiveController
{
    /**
     * {@inheritdoc}
     */
    public $modelClass = 'app\modules\v2\models\Classes';

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


    public function actionTeacherCalender($teacher_id, $date = null, $class_id = null, $homework = 1, $live_class = 1)
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_SCHOOL) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }
        if (!$school = Schools::findOne(['id' => Utility::getSchoolAccess()]))
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'School not found');
        $school_id = $school->id;


        $model = new \yii\base\DynamicModel(compact('school_id', 'teacher_id'));
        $model->addRule(['school_id'], 'required')
            ->addRule(['school_id', 'teacher_id'], 'integer')
            ->addRule(['teacher_id'], 'exist', ['targetClass' => SchoolTeachers::className(), 'targetAttribute' => ['teacher_id' => 'teacher_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Validation failed');
        }


        $homeworks = Homeworks::find()
            ->select([
                'homeworks.id',
                'homeworks.title',
                'homeworks.subject_id',
                'homeworks.class_id',
                'homeworks.created_at',
                'homeworks.close_date as datetime',
                'DATE(homeworks.close_date) as date',
                'TIME(homeworks.close_date) as time',
                new Expression("'homework' as type"),
                'user.firstname as teacher_firstname',
                'user.lastname as teacher_lastname',
                'user.id as teacher_id',
                'user.image as teacher_image',
            ])
            ->innerJoin('user', 'user.id = homeworks.teacher_id')
            ->innerJoin('school_teachers', 'school_teachers.teacher_id = homeworks.teacher_id')
            ->where([
                'school_teachers.school_id' => $school_id,
                'homeworks.publish_status' => 1
            ])->asArray();

        $live_classes = TutorSession::find()
            ->select([
                'tutor_session.id',
                'tutor_session.title',
                'tutor_session.subject_id',
                'tutor_session.class as class_id',
                'tutor_session.created_at',
                'tutor_session.availability as datetime',
                'DATE(tutor_session.availability) as date',
                'TIME(tutor_session.availability) as time',
                new Expression("'live_class' as type"),
                'user.firstname as teacher_firstname',
                'user.lastname as teacher_lastname',
                'user.id as teacher_id',
                'user.image as teacher_image',
            ])
            ->innerJoin('user', 'user.id = tutor_session.requester_id')
            ->innerJoin('school_teachers', 'school_teachers.teacher_id = tutor_session.requester_id')
            ->where([
                'is_school' => 1
            ])->asArray();

        if ($date) {
            $homeworks = $homeworks
                ->andWhere([
                    'DATE(close_date)' => $date,
                ]);

            $live_classes = $live_classes
                ->andWhere([
                    'DATE(availability)' => $date,
                ]);
        } else {
//            $live_classes = $live_classes->andWhere(['DAY(CURDATE())' => 'DAY(created_at)']);
//            $homeworks = $homeworks->andWhere(['DAY(CURDATE())' => 'DAY(created_at)']);
        }

        if ($class_id) {
            $homeworks = $homeworks
                ->andWhere([
                    'class_id' => $class_id,
                ]);

            $live_classes = $live_classes
                ->andWhere([
                    'tutor_session.class' => $class_id,
                ]);
        }

        if (!empty($teacher_id)) {
            $homeworks = $homeworks->andWhere(['homeworks.teacher_id' => $teacher_id]);
            $live_classes = $live_classes->andWhere(['tutor_session.requester_id' => $teacher_id]);
        }

        if ($homework == SharedConstant::VALUE_ZERO) {
            $homeworks = [];
        }

        if ($live_class == SharedConstant::VALUE_ZERO) {
            $live_classes = [];
        }

        $homeworks = $homeworks ? $homeworks->all() : [];
        $live_classes = $live_classes ? $live_classes->all() : [];

        $teacher_calender = array_merge($homeworks, $live_classes);
        if (!$teacher_calender) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher calender is empty');
        }

        array_multisort(array_column($teacher_calender, "datetime"), SORT_ASC, $teacher_calender);


        return (new ApiResponse)->success(
            $teacher_calender,
            ApiResponse::SUCCESSFUL,
            'Teacher calender found'
        );
    }
}
