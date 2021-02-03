<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Classes;
use app\modules\v2\models\Homeworks;
use app\modules\v2\models\Parents;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentDetails;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use Yii;
use app\modules\v2\components\{SharedConstant, Utility};
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


/**
 * Teacher controller
 */
class StudentController extends ActiveController
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
    }


    public function actionStudentClassHomework($student_id)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('student_id', 'school_id'));
        $model->addRule(['student_id'], 'exist', ['targetClass' => StudentSchool::className(), 'targetAttribute' => ['student_id' => 'student_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $classes = Classes::find()
            ->innerJoin('student_school', 'student_school.class_id = classes.id')
            ->where(['student_school.student_id' => $student_id]);


        if (!$classes->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes not found!');
        }

        return (new ApiResponse)->success($classes->all(), ApiResponse::SUCCESSFUL, 'Classes found');
    }

    public function actionStudentHomework($student_id)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;

        $homeworks = Homeworks::find()->where(['student_id' => $student_id])->all();
        if (!$homeworks) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homeworks not found!');
        }

        return (new ApiResponse)->success($homeworks, ApiResponse::SUCCESSFUL, 'Homeworks found');
    }

    /**
     * Get student profile details
     * @param $student_id
     * @return ApiResponse
     */
    public function actionProfile($student_id)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('student_id', 'school_id'));
        $model->addRule(['student_id'], 'exist', ['targetClass' => StudentSchool::className(), 'targetAttribute' => ['student_id' => 'student_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR, 'This student does not belong to your school');
        }

        $detail = StudentDetails::findOne(['id' => $student_id]);
        return (new ApiResponse)->success($detail);
    }

    /**
     * Remove student from class and school.
     *
     * @param $student_id
     * @return ApiResponse
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionRemoveStudent($student_id)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        if (!$student = StudentSchool::findOne(['school_id' => $school->id, 'student_id' => $student_id])) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student does not exist');
        }

        if (!$student->delete()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Student not removed');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Student Removed!');
    }

    public function actionUpdateClass()
    {
        $student_id = Yii::$app->request->post('student_id');
        $class_id = Yii::$app->request->post('class_id');

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('student_id', 'class_id', 'school_id'));
        $model->addRule(['student_id', 'class_id'], 'required');
        $model->addRule(['student_id'], 'exist', ['targetClass' => StudentSchool::className(), 'targetAttribute' => ['student_id', 'school_id']]);
        $model->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['school_id', 'class_id' => 'id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $model = StudentSchool::findOne(['student_id' => $student_id, 'school_id' => $school_id, 'status' => 1, 'is_active_class' => 1]);
        $model->class_id = $class_id;
        if ($model->save())
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Student class updated');
        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save');
    }

    public function actionStudents($student = null, $class = null, $license = null)
    {

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $classes = StudentSchool::find()
            ->where(['school_id' => $school->id]);

        if (!$classes->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No parent available!');
        }

        $models = StudentSchool::find()
            ->alias('ss')
            ->select([
                'u.id',
                'u.firstname student_firstname',
                'u.lastname student_lastname',
                'u.image student_image',
                'u.lastname student_lastname',
                'pu.id parent_id',
                'pu.firstname parent_firstname',
                'pu.lastname parent_lastname',
                'pu.phone parent_phone',
                'pu.email parent_email',
                'p.role relationship',
                'pu.image parent_image',
                'ss.subscription_status',
                'cl.class_name',
            ])
            ->innerJoin('user u', 'u.id = ss.student_id')
            ->leftJoin('parents p', 'p.student_id = ss.student_id AND p.status = 1')
            ->leftJoin('user pu', 'pu.id = p.parent_id AND p.status = 1')
            ->leftJoin('classes cl', 'cl.id = ss.class_id')
            ->where(['ss.school_id' => $school->id, 'ss.status' => 1, 'ss.is_active_class' => 1]);

        if (!empty($class)) {
            $models = $models->andWhere(['cl.id' => $class]);
        }

        if (!empty($student)) {
            $models = $models->andFilterWhere(['OR', ['like', 'u.lastname', '%' . $student . '%', false],
                ['like', 'u.firstname', '%' . $student . '%', false],
                ['like', 'u.code', '%' . $student . '%', false]]);
        }

        if (!empty($license) && $license != 'all') {
            if ($license == 'disable')
                $license = null;
            $models = $models->andWhere(['ss.subscription_status' => $license]);
        }

        $models = $models->groupBy('u.id')->asArray();

        $dataProvider = new ActiveDataProvider([
            'query' => $models,
            'pagination' => [
                'pageSize' => 20,
                'validatePage' => false,
            ],
        ]);


        $return = [
            'students' => $dataProvider->getModels(),
            'license' => Utility::SchoolStudentSubscriptionDetails($school)
        ];

        return (new ApiResponse)->success($return, ApiResponse::SUCCESSFUL, $models->count(), $dataProvider);

    }

    public function actionParentChildren($parent_id)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $model = UserModel::find()
            ->select([
                'user.id',
                'firstname',
                'lastname',
                'image',
                'cl.class_name',
            ])
            ->innerJoin('parents p', 'p.student_id = user.id AND p.status = 1')
            ->innerJoin('student_school ss', 'ss.student_id = p.student_id AND ss.status = 1')
            ->leftJoin('classes cl', 'cl.id = ss.class_id')
            ->where(['p.parent_id' => $parent_id, 'ss.school_id' => $school->id])->asArray()->all();

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL);
    }

    public function actionModifySubscription()
    {
        $status = Yii::$app->request->post('status');
        $student_id = Yii::$app->request->post('student_id');

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('student_id', 'school_id', 'status'));
        $model->addRule(['student_id', 'status'], 'required');
        $model->addRule(['status'], 'in', ['range' => ['basic', 'premium', 'disable']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!is_array($student_id)) {
            return (new ApiResponse)->error('Student id must be an array.', ApiResponse::VALIDATION_ERROR);
        }

        if (count($student_id) > StudentSchool::find()->where(['student_id' => $student_id, 'school_id' => $school_id, 'status' => 1, 'is_active_class' => 1])->count()) {
            return (new ApiResponse)->error('One or more student ID is invalid', ApiResponse::VALIDATION_ERROR);
        }

        $license = Utility::SchoolStudentSubscriptionDetails($school);
        if (isset($license[$status]['remaining']) && count($student_id) > $license[$status]['remaining']) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, "Invalid action or no enough room for '$status' subscription.");
        }

        if ($status == 'disable') {
            $status = null;
        }

        if (StudentSchool::updateAll(['subscription_status' => $status], ['student_id' => $student_id, 'school_id' => $school_id, 'status' => 1, 'is_active_class' => 1]))
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Student subscriptions updated');

        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not update');
    }


}

