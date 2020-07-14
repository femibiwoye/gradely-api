<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Parents;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;


/**
 * Schools/Parent controller
 */
class ParentsController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\SchoolParents';

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
            'class' => HttpBearerAuth::className(),
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
        unset($actions['view']);
        return $actions;
    }


    public function actionIndex()
    {
        $school = Schools::findOne(['user_id' => Yii::$app->user->id]);
        $classes = StudentSchool::find()
            ->where(['school_id' => $school->id]);

        if (!$classes->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No parent available!');
        }
        //Get Students ID
        $studentID = ArrayHelper::getColumn($classes->all(), 'student_id');
        $parentsID = Parents::findAll(['student_id' => $studentID]);

        $parentsList = UserModel::find()
            ->where(['AND', ['id' => $parentsID, 'type' => 'parent'], ['<>', 'status', 0]])
            ->all();

        $parentLists = [];
        foreach ($parentsList as $index => $parent) {
            $children = $parent->parentChildren;
            $parentLists[$index] = array_merge(ArrayHelper::toArray($parent), ['children' => ArrayHelper::toArray($children)]);
        }

         $dataProvider = new ArrayDataProvider([
            'allModels' =>$parentLists,
            'sort' => [
                'attributes' => ['id', 'firstname', 'lastname','email'],
            ],
            'pagination' => [
                'pageSize' => 30,
            ],
        ]);



        return (new ApiResponse)->success($dataProvider->allModels, ApiResponse::SUCCESSFUL, count($parentsID) . ' parents found');
    }

    /**
     * {@inheritdoc}
     */

}