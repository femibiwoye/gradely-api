<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\User;
use app\modules\v2\models\UserModel;
use Yii;
use yii\data\ActiveDataProvider;
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
        unset($actions['index']);
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

        $parents = UserModel::find()
            ->alias('parent')
//            ->select([
//                'parent.id',
//                'parent.firstname',
//                'parent.lastname',
//                'parent.phone',
//                'parent.email',
//                'parent.image',
//                'parent.type',
//            ])
            //->leftJoin(['user_profile', 'user_profile.user_id = parent.id'])
            ->joinWith(['parentChildren'])
            ->where(['parent.id' => $studentID])
            ->groupBy(['parent.id']);
        //->asArray();
        // ->all();

        $dataProvider = new ActiveDataProvider([
            'query' => $parents
        ]);


        return (new ApiResponse)->success($dataProvider->getModels(), ApiResponse::SUCCESSFUL, $classes->count() . ' parents found');
    }

    /**
     * {@inheritdoc}
     */

}