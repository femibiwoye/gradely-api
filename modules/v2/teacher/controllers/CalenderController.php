<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\components\Utility;
use app\modules\v2\models\Schools;
use Yii;
use yii\data\ArrayDataProvider;
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


    public function actionTeacherCalender($teacher_id, $date = null, $class_id = null, $homework = 1, $live_class = 1, $exam = 1, $month = null)
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_SCHOOL && Yii::$app->user->identity->type != SharedConstant::TYPE_TEACHER) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        if (Yii::$app->user->identity->type == SharedConstant::TYPE_SCHOOL) {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            $school_id = $school->id;
        } else {
            $teacher = SchoolTeachers::findOne(['teacher_id' => Yii::$app->user->id]);
            $school_id = $teacher ? $teacher->school_id : null;
            $teacher_id = $teacher ? $teacher->teacher_id : Yii::$app->user->id;
        }

        if (!isset($school_id))
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'User record not found');


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
                'subjects.name as subject_name',
                'classes.class_name',
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
            ->innerJoin('subjects', 'subjects.id = homeworks.subject_id')
            ->innerJoin('classes', 'classes.id = homeworks.class_id')
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
                'subjects.name as subject_name',
                'classes.class_name',
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
            ->innerJoin('subjects', 'subjects.id = tutor_session.subject_id')
            ->innerJoin('classes', 'classes.id = tutor_session.class')
            ->where([
                'is_school' => 1,
                'school_teachers.school_id' => $school_id,
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
            if ($month) {
                $dates = explode('-',$month,2);
                $month =$dates[0];
                $year =$dates[1];
                $homeworks = $homeworks->andWhere(['MONTH(close_date)' => $month, 'YEAR(close_date)' => $year]);
                $live_classes = $live_classes->andWhere(['MONTH(availability)' => $month, 'YEAR(availability)' => $year]);
            } else {
                $live_classes = $live_classes->andWhere(['DATE(availability)' => new Expression('DATE(CURDATE())')]);
                $homeworks = $homeworks->andWhere(['DATE(close_date)' => new Expression('DATE(CURDATE())')]);
            }
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

        if ($exam == 0) {
            $homeworks = $homeworks->andWhere(['!=', 'homeworks.tag', 'exam']);
        }

        if (!empty($teacher_id)) {
            $homeworks = $homeworks->andWhere(['homeworks.teacher_id' => $teacher_id]);
            $live_classes = $live_classes->andWhere(['tutor_session.requester_id' => $teacher_id]);
        }

        if ($homework == SharedConstant::VALUE_ZERO) {
            $homeworks = $homeworks->andWhere(['!=', 'homeworks.tag', 'homework']);
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


        return (new ApiResponse)->success($teacher_calender, ApiResponse::SUCCESSFUL, 'Teacher calender found');
    }
}
