<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolTeachers;
use app\modules\v2\models\UserModel;
use Yii;
use app\modules\v2\models\{User, ApiResponse};
use app\modules\v2\components\{SharedConstant};
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


/**
 * Teacher controller
 */
class TeacherController extends ActiveController
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

    /**
     * Login action.
     *
     * @return Response|string
     */

    public function actionIndex()
    {

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $teachersID = SchoolTeachers::find()->where(['school_id' => $school->id, 'status' => 1])->all();
        $model = UserModel::find()->where(['type' => 'teacher', 'id' => ArrayHelper::getColumn($teachersID, 'teacher_id')])
            ->with(['teacherClassesList', 'teacherSubjectList'])
            ->groupBy(['id']);

        $teachers = new ActiveDataProvider([
            'query' => $model,
            'sort' => [
                'attributes' => ['id', 'firstname', 'lastname', 'email'],
                'defaultOrder' => [
                    'id' => SORT_DESC,
                    'firstname' => SORT_ASC,
                ]
            ],
            'pagination' => ['pageSize' => 16]
        ]);

        return (new ApiResponse)->success($teachers->getModels());
    }

    public function actionPending()
    {

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $teachersID = SchoolTeachers::find()->where(['school_id' => $school->id, 'status' => 0])->all();
        $model = UserModel::find()->where(['type' => 'teacher', 'id' => ArrayHelper::getColumn($teachersID, 'teacher_id')])
            ->with(['teacherFirstClass'])
            ->groupBy(['id']);

        $teachers = new ActiveDataProvider([
            'query' => $model,
            'sort' => [
                'attributes' => ['id', 'firstname', 'lastname', 'email'],
                'defaultOrder' => [
                    'id' => SORT_DESC,
                    'firstname' => SORT_ASC,
                ]
            ],
            'pagination' => ['pageSize' => 16]
        ]);

        return (new ApiResponse)->success($teachers->getModels());
    }

    public function actionAcceptTeacher($id = 0)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        if ($id > 0 && $model = SchoolTeachers::findOne(['teacher_id' => $id, 'school_id' => $school->id, 'status' => 0])) {
            $model->status = 1;
            if ($model->save())
                return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Teacher accepted!');
        }

        if ($id == 0) {
            if (SchoolTeachers::updateAll(['status' => 1], ['school_id' => $school->id, 'status' => 0])) {
                return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Pending teachers has been accepted!');
            }
        }

        return (new ApiResponse)->success(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid requests');
    }

    public function actionDeclineTeacher($id = null)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        if ($id > 0 && $model = SchoolTeachers::findOne(['teacher_id' => $id, 'status' => 0])) {
            $model->delete();
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Teacher declined & removed!');
        }

        if ($id == 0) {
            if (SchoolTeachers::deleteAll(['school_id' => $school->id, 'status' => 0])) {
                return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Pending teachers declined and removed!');
            }
        }

        return (new ApiResponse)->success(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid requests');
    }


}

