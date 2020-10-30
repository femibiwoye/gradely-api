<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\components\InputNotification;
use app\modules\v2\components\{Utility, Pricing};
use app\modules\v2\models\Remarks;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolTeachers;
use app\modules\v2\models\StudentDetails;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\TeacherClassSubjects;
use app\modules\v2\models\UserModel;
use Yii;
use app\modules\v2\models\{Classes,
    ApiResponse,
    TeacherClass,
    User,
    SearchSchool,
    StudentProfile,
    SubjectTopics,
    Questions};
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\components\SharedConstant;
use app\modules\v2\teacher\models\{TeacherSchoolForm, StudentClassForm, DeleteStudentForm, AddStudentForm};

/**
 * ClassController implements the CRUD actions for Classes model.
 */
class ClassController extends ActiveController
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

    /**
     * Displays a single Classes model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($code)
    {
        $class = $this->modelClass::find()->where(['class_code' => $code])->joinWith('school')->asArray()->one();
        if (!$class) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found!');
        }

        return (new ApiResponse)->success($class, ApiResponse::SUCCESSFUL, 'Class found');
    }

    public function actionAddTeacher()
    {
        $class_code = Yii::$app->request->post('code');
        if (!$class_code) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Provide class code.');
        }

        $class = $this->modelClass::findOne(['class_code' => $class_code]);
        if (!$class) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class not found!');
        }

        if (!$class->school) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'School not found!');
        }


        if (TeacherClass::find()->where(['teacher_id' => Yii::$app->user->id, 'school_id' => $class->school->id, 'class_id' => $class->id, 'status' => 1])->exists())
            return (new ApiResponse)->success(null, ApiResponse::ALREADY_TAKEN, 'Teacher already added to class!');

        $model = new TeacherClass;
        $model->teacher_id = Yii::$app->user->id;
        $model->school_id = $class->school->id;
        $model->class_id = $class->id;
        $model->status = 1;
        if (!$model->save()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Teacher is not successfully added!');
        }
        $model->addSchoolTeacher(1);

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Teacher added successfully');
    }

    public function actionSchool($id)
    {
        $classes = $this->modelClass::find()
            ->where(['school_id' => $id])
            ->all();

        if (!$classes) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes not found!');
        }

        return (new ApiResponse)->success($classes, ApiResponse::SUCCESSFUL, 'Classes found');
    }

    public function actionClassTeacher($class_id)
    {
        if (!TeacherClass::find()->where(['class_id' => $class_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1])->count()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You do not believe to this class');
        }
        $teacherID = ArrayHelper::getColumn(TeacherClass::find()->where(['class_id' => $class_id, 'status' => 1])->all(), 'teacher_id');
        $model = UserModel::find()->where(['type' => 'teacher', 'id' => $teacherID])
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
            'pagination' => ['pageSize' => 20]
        ]);

        return (new ApiResponse)->success($teachers->getModels(), null, null, $teachers);
    }

    public function actionTeacherClass()
    {
        $teacher = User::find()
            ->where(['id' => Yii::$app->user->id])
            ->andWhere(['type' => SharedConstant::TYPE_TEACHER])
            ->one();

        if (!$teacher || !$teacher->classes) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Classes not found!');
        }

        $notification = new InputNotification();
        if (!$notification->NewNotification('teacher_assigned_class', [['teacher_id', Yii::$app->user->id]]))
            return false;

        return (new ApiResponse)->success($teacher->classes, ApiResponse::SUCCESSFUL, 'Classes found');
    }

    public function actionSearchSchool($q)
    {
        $school = SearchSchool::find()->where(['like', 'name', $q])->limit(6)->all();
        if (!$school) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'School record not found!');
        }

        return (new ApiResponse)->success($school, ApiResponse::SUCCESSFUL, 'School record found');
    }

    public function actionAddTeacherSchool($status = 0)
    {
        $form = new TeacherSchoolForm;
        $form->attributes = Yii::$app->request->post();
        $form->teacher_id = Yii::$app->user->id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (TeacherClass::find()->where(['teacher_id' => Yii::$app->user->id, 'school_id' => $form->school_id, 'class_id' => $form->class_id])->exists())
            return (new ApiResponse)->success(null, ApiResponse::ALREADY_TAKEN, 'Teacher already added to class!');

        if (!$model = $form->addTeacherClass($status)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not added!');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record added');
    }

    public function actionStudentsInClass($class_id)
    {
        $form = new StudentClassForm;
        $form->class_id = $class_id;
        $form->teacher_id = Yii::$app->user->id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$data = $form->getStudents()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Records not found');
        }

        return (new ApiResponse)->success($data, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionDeleteStudent($student_id, $class_id)
    {
        $form = new DeleteStudentForm;
        $form->teacher_id = Yii::$app->user->id;
        $form->student_id = $student_id;
        $form->class_id = $class_id;
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$form->deleteStudent()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Records not deleted');
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Record deleted');
    }

    private function SchoolID($teacher_id)
    {
        $model = SchoolTeachers::find()->select('school_id')->where(['teacher_id' => $teacher_id])->one();
        return $model->school_id;
    }

    public function actionAddStudent()
    {

        $form = new AddStudentForm;
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $school_student_limit = Pricing::SubscriptionStatus(null, null, false);
        if ($school_student_limit['unused_student'] < 1) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Students limit exceeded');
        }
        if (count($form->students) > $school_student_limit['unused_student']) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not enough room to add new students');
        }


        if (!$user = $form->addStudents(SharedConstant::TYPE_STUDENT)) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Record not added');
        }

        return (new ApiResponse)->success($user, null, 'You have successfully added students');
    }

    public function actionGetStudent($id, $remark = 0)
    {
        $detail = StudentDetails::findOne(['id' => $id]);
        if (!$detail->checkStudentInTeacherClass()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'This student does not belong to your school');
        }

        if ($remark == 1) {
            $detail = $detail->remarks;
        }

        return (new ApiResponse)->success($detail);
    }

    public function actionSendStudentRemark($id)
    {
        $remark = Yii::$app->request->post('remark');
        $form = new \yii\base\DynamicModel(compact('remark'));
        $form->addRule(['remark'], 'required');

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $detail = StudentDetails::findOne(['id' => $id]);
        if (!$detail->checkStudentInTeacherClass()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'This student does not belong to your school');
        }

        $model = new Remarks();
        $model->type = 'student';
        $model->creator_id = Yii::$app->user->id;
        $model->receiver_id = $id;
        $model->remark = $remark;
        if ($model->save())
            return (new ApiResponse)->success($model);
        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not send remark');
    }

    public function actionRemoveClass($class_id)
    {
        $teacherClass = TeacherClass::findOne(['class_id' => $class_id, 'teacher_id' => Yii::$app->user->id]);
        if (!$teacherClass) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class either does not exist or invalid!');
        }

        if ($teacherClass->delete()) {
            TeacherClassSubjects::deleteAll(['teacher_id' => Yii::$app->user->id, 'class_id' => $class_id]);
        }
        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Teacher removed!');
    }

    public function actionTopics($term = 0)
    {
        $class_id = Yii::$app->request->get('global_class_id');
        $subject_id = Yii::$app->request->get('subject_id');
        $form = new \yii\base\DynamicModel(compact('class_id', 'subject_id'));
        $form->addRule(['class_id', 'subject_id'], 'required');
        $form->addRule(['class_id'], 'exist', ['targetClass' => SubjectTopics::className(), 'targetAttribute' => ['class_id' => 'class_id', 'subject_id' => 'subject_id']]);

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if ($term == 1) {
            $terms = ['first', 'second', 'third'];
            $model = [];
            foreach ($terms as $term) {
                $model[] = ['term' => $term, 'topics' => SubjectTopics::find()
                    ->where(['subject_id' => $subject_id, 'class_id' => $class_id, 'term' => $term])
                    ->orderBy(['week_number' => SORT_ASC])
                    //->limit(2)
                    ->all()];
            }
        } else {
            $model = SubjectTopics::find()
                ->where(['subject_id' => $subject_id, 'class_id' => $class_id])
                ->orderBy(['term' => SORT_ASC, 'week_number' => SORT_ASC])
                //->limit(6)
                ->all();
        }

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, count($model) . ' record found');
    }

    public function actionSearchTopic()
    {
        $class_id = Yii::$app->request->get('global_class_id');
        $subject_id = Yii::$app->request->get('subject_id');
        $topic = Yii::$app->request->get('topic');
        $form = new \yii\base\DynamicModel(compact('class_id', 'subject_id', 'topic'));
        $form->addRule(['class_id', 'subject_id', 'topic'], 'required');

        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        $model = SubjectTopics::find()
            ->where(['class_id' => $class_id, 'subject_id' => $subject_id])
            ->andWhere(['like', 'topic', '%' . $topic . '%', false])
            ->limit(6)
            ->all();

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionClassDetails($class_id)
    {
        $teacherClass = TeacherClass::findOne(['class_id' => $class_id, 'teacher_id' => Yii::$app->user->id]);
        if (!$teacherClass) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class either does not exist or invalid!');
        }

        $getClass = Classes::find()
            ->where(['classes.id' => $class_id])
            ->joinWith(['school', 'globalClass'])
            ->asArray()
            ->one();
        if ($getClass) {
            return (new ApiResponse)->success($getClass, ApiResponse::SUCCESSFUL, 'Class found');
        }

        return (new ApiResponse)->success(null, ApiResponse::NOT_FOUND, 'Class not found!');
    }
}
