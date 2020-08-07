<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\{Utility, SharedConstant};
use app\modules\v2\models\{Schools, StudentSchool, Classes, ApiResponse, TeacherClass, User, Homeworks};
use app\modules\v2\school\models\ClassForm;
use Yii;
use yii\filters\AccessControl;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;


/**
 * Schools/Parent controller
 */
class ClassesController extends ActiveController
{
    public $modelClass = 'app\modules\v2\models\Classes';

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
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $getAllClasses = Classes::find()
            ->select([
                'classes.id',
                'classes.slug',
                'class_code',
                'class_name',
                'abbreviation',
                'global_class_id',
                'school_id',
                'schools.name school_name'
            ])
            ->leftJoin('schools', 'schools.id = classes.school_id')
            ->where(['school_id' => $school->id])
            ->asArray();


        if (!$getAllClasses->exists()) {
            return (new ApiResponse)->success(null, ApiResponse::NO_CONTENT, 'No classes available!');
        }
        return (new ApiResponse)->success($getAllClasses->all(), ApiResponse::SUCCESSFUL, $getAllClasses->count() . ' classes found');
    }

    public function actionGroupClasses()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $globalClasses = Utility::getMyGlobalClassesID($school->school_type);
        $classes = [];

        foreach ($globalClasses as $class) {
            $globalTemp = Utility::getGlobalClasses($class->id, $school);
            $classes[] = array_merge($globalTemp, ['classes' => $class->getSchoolClasses($school->id)]);
        }

        return (new ApiResponse)->success($classes, ApiResponse::SUCCESSFUL);
    }

    public function actionView($id)
    {
        $getClass = Classes::find()
            ->where(['school_id' => Utility::getSchoolAccess(), 'classes.id' => $id])
            ->joinWith(['school', 'globalClass'])
            ->asArray()
            ->one();
        if ($getClass) {
            return (new ApiResponse)->success($getClass, ApiResponse::SUCCESSFUL, 'Class found');
        }

        return (new ApiResponse)->success(null, ApiResponse::NOT_FOUND, 'Class not found!');
    }

    /** Create a single class
     * @return ApiResponse
     */
    public function actionCreate()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        $form = new ClassForm(['scenario' => ClassForm::SCENERIO_CREATE_CLASS]);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$model = $form->newClass($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class is not updated');
        }

        return (new ApiResponse)->success($model);
    }

    public function actionUpdate()
    {
        $form = new ClassForm(['scenario' => ClassForm::SCENERIO_UPDATE_CLASS]);
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $classModel = Classes::find()->where(['school_id' => $school->id, 'id' => $form->id]);
        if (!$classModel->exists()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'This is not a valid class!');
        }

        $model = $classModel->one();
        $model->class_name = $form->class_name;
        $model->save();

        return (new ApiResponse)->success($model,null,'Class successfully updated.');
    }

    public function actionGenerateClasses()
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

//        if (count($school->classes) > 5)
//            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes are already generated');

        $form = new ClassForm(['scenario' => ClassForm::SCENERIO_GENERATE_CLASSES]);
        $form->attributes = Yii::$app->request->post();

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!$form->generateClasses($school)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes was not generated');
        }
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        return (new ApiResponse)->success($school->classes, null, count($school->classes) . ' classes generated!');
    }

    public function actionStudentInClass($class_id) {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id'));
        $model->addRule(['class_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['class_id' => 'class_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $student_ids = ArrayHelper::getColumn(StudentSchool::find()->where(['class_id' => $class_id])->all(), 'student_id');

        $students = User::find()->where(['id' => $student_ids])
                                ->andWhere(['type' => SharedConstant::ACCOUNT_TYPE[3]])
                                ->orderBy('id DESC');

        if (!$students) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Students not found');
        }

        return (new ApiResponse)->success($students->all(), ApiResponse::SUCCESSFUL, 'Students record found');

    }

    public function actionStudentClasses($student_id) {
        $school_id = Utility::getSchoolAccess()[0];
        $model = new \yii\base\DynamicModel(compact('student_id', 'school_id'));
        $model->addRule(['student_id'], 'exist', ['targetClass' => StudentSchool::className(), 'targetAttribute' => ['student_id' => 'student_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $classes = Classes::find()
                    ->innerJoin('student_school', 'student_school.class_id = classes.id')
                    ->where(['student_school.student_id' => $student_id]);

        
        if (!$classes) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes not found!');
        }

        return (new ApiResponse)->success($classes->all(), ApiResponse::SUCCESSFUL, 'Classes found');
    }

    public function actionStudentHomeworks($student_id) {
        $school_id = Utility::getSchoolAccess()[0];
        $model = new \yii\base\DynamicModel(compact('student_id', 'school_id'));
        $model->addRule(['student_id'], 'exist', ['targetClass' => Homeworks::className(), 'targetAttribute' => ['student_id' => 'student_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $homeworks = Homeworks::find()->where(['student_id' => $student_id])->all();
        if (!$homeworks) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Homeworks not found!');
        }

        return (new ApiResponse)->success($homeworks, ApiResponse::SUCCESSFUL, 'Homeworks found');
    }
}