<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\Utility;
use app\modules\v2\models\ExamType;
use app\modules\v2\models\Schools;
use app\modules\v2\school\models\PreferencesForm;
use app\modules\v2\school\models\SchoolProfile;
use app\modules\v2\teacher\models\TeacherUpdateEmailForm;
use app\modules\v2\teacher\models\TeacherUpdatePasswordForm;
use app\modules\v2\teacher\models\UpdateTeacherForm;
use Yii;
use app\modules\v2\models\{User, ApiResponse, UserPreference};
use app\modules\v2\components\{SharedConstant};
use yii\db\Expression;
use yii\filters\AccessControl;
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
        $examType = ExamType::find()->where(['OR', ['school_id' => null], ['school_id' => $school->id]]);
        return (new ApiResponse)->success($examType->all(), ApiResponse::SUCCESSFUL, $examType->count() . ' classes found');
    }

    public function actionNewCurriculum()
    {
        $form = new PreferencesForm(['scenario' => 'curriculum-request']);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        if (!$model = $form->updateFormats($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class is not updated');
        }

        return (new ApiResponse)->success($model);
    }

}

