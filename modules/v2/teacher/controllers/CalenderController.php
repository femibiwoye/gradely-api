<?php

namespace app\modules\v2\teacher\controllers;

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

    public function actionTeacherCalender($teacher_id)
    {
        if (Yii::$app->user->identity->type != SharedConstant::TYPE_SCHOOL) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Authentication failed');
        }

        $date = Yii::$app->request->get('date');
        $class_id = Yii::$app->request->get('class_id');
        $homework = Yii::$app->request->get('homework');
        $live_class = Yii::$app->request->get('live_class');
        $school_id = Yii::$app->user->id;
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
                        new Expression("'homework' as type"),
                        'user.firstname as teacher_firstname',
                        'user.lastname as teacher_lastname',
                        'user.id as teacher_id',
                        'user.image as teacher_image',
                    ])
                    ->innerJoin('user', 'user.id = homeworks.teacher_id')
                    ->where([
                        'school_id' => Yii::$app->user->id,
                        'DAY(CURDATE())' => 'DAY(created_at)',
                    ]);

        $live_classes = TutorSession::find()
                        ->select([
                            'tutor_session.id',
                            'tutor_session.title',
                            'tutor_session.subject_id',
                            'tutor_session.class as class_id',
                            'tutor_session.created_at',
                            new Expression("'live_class' as type"),
                            'user.firstname as teacher_firstname',
                            'user.lastname as teacher_lastname',
                            'user.id as teacher_id',
                            'user.image as teacher_image',
                        ])
                        ->innerJoin('user', 'user.id = tutor_session.requester_id')
                        ->where([
                            'DAY(CURDATE())' => 'DAY(created_at)',
                        ]);

        if ($date) {
            $homeworks = $homeworks
                    ->where([
                       'school_id' => Yii::$app->user->id,
                       'created_at' => $date, 
                    ]);

            $live_classes = $live_classes
                    ->where([
                        'created_at' => $date,
                    ]);
        }

        if ($class_id) {
            $homeworks = $homeworks
                    ->where([
                       'school_id' => Yii::$app->user->id,
                       'created_at' => $date,
                       'class_id' => $class_id,
                    ]);

            $live_classes = $live_classes
                    ->where([
                        'created_at' => $date,
                        'class' -> $class_id,
                    ]);
        }

        if (!empty($teacher_id)) {
            $homeworks = $homeworks->andWhere(['teacher_id' => $teacher_id]);
            $live_classes = $live_classes->andWhere(['requester_id' => $teacher_id]);
        }

        if ($homework == SharedConstant::VALUE_ZERO) {
            return (new ApiResponse)->success($live_classes->all(), ApiResponse::SUCCESSFUL, 'Teacher calender found');
        }

        if ($live_class == SharedConstant::VALUE_ZERO) {
            return (new ApiResponse)->success($homeworks->all(), ApiResponse::SUCCESSFUL, 'Teacher calender found');
        }

        $teacher_calender = array_merge($homeworks->all(), $live_classes->all());
        if (!$teacher_calender) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher calender is empty');
        }

        return (new ApiResponse)->success(
            $teacher_calender,
            ApiResponse::SUCCESSFUL,
            'Teacher calender found'
        );
    }
}
