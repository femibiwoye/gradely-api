<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\Utility;
use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Parents;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\UserModel;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


/**
 * Schools/Parent controller
 */
class ParentsController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Parents';

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


    public function actionIndex()
    {
        // return date_default_timezone_get().' - '.date('d M Y h:i:s');
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $classes = StudentSchool::find()
            ->where(['school_id' => $school->id]);

        if (!$classes->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No parent available!');
        }

        //Get Students ID
        $studentID = ArrayHelper::getColumn($classes->all(), 'student_id');
        $parentsID = Parents::find()->where(['student_id' => $studentID, 'status' => 1])->all();

        $parentsList = UserModel::find()
            ->with(['parentChildren'])
            ->where(['AND', ['id' => $parentsID, 'type' => 'parent'], ['<>', 'status', 0]]);

        $dataProvider = new ActiveDataProvider([
            'query' => $parentsList,
            'sort' => [
                'attributes' => ['id', 'firstname', 'lastname', 'email'],
                'defaultOrder' => [
                    'id' => SORT_DESC,
                    'firstname' => SORT_ASC,
                ]
            ],
            'pagination' => [
                //'defaultPageSize' => 1, //With this, you can specify how many number of content you want per page
                'pageSize' => 20, // This is a fixed number of content to be rendered per page.
            ],
        ]);

        return (new ApiResponse)->success($dataProvider->getModels(), ApiResponse::SUCCESSFUL, count($parentsID) . ' parents found');
    }
}