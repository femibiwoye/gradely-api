<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\SchoolNamingFormat;
use app\modules\v2\models\SchoolRole;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolType;
use app\modules\v2\school\models\SchoolProfile;
use Yii;
use yii\filters\AccessControl;
use yii\rest\ActiveController;


/**
 * Schools/Parent controller
 */
class GeneralController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\SchoolType';
    //public $modelFormat = 'app\modules\v2\models\SchoolNamingFormat';

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
     * This returns school types.
     * e.g primary, secondary, primary and secondary
     * @return ApiResponse
     */
    public function actionSchoolType()
    {
        return (new ApiResponse)->success(SchoolType::find()->where(['status' => 1])->all(), ApiResponse::SUCCESSFUL, 'Found');
    }

    /**
     * This returns the format to be used in naming the classes.
     * E.g Primary, Junior Secondary school, Senior Secondary school OR Year1-12
     * @return ApiResponse
     */
    public function actionSchoolNamingFormat()
    {
        return (new ApiResponse)->success(SchoolNamingFormat::find()->where(['status' => 1])->all(), ApiResponse::SUCCESSFUL, 'Found');
    }

    public function actionUpdateFormatType()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $form = new SchoolProfile(['scenario' => 'format-type']);
        $form->attributes = Yii::$app->request->post();

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$school = $form->updateFormats($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class is not updated');
        }

        return (new ApiResponse)->success($school);
    }

    public function actionSchoolRoles()
    {
        return (new ApiResponse)->success(SchoolRole::find()->select('title, slug')->where(['status' => 1])->all(), ApiResponse::SUCCESSFUL, 'Found');

    }


}