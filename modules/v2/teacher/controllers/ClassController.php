<?php

namespace app\modules\v2\teacher\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\components\InputNotification;
use app\modules\v2\components\{Utility, Pricing};
use app\modules\v2\models\Remarks;
use app\modules\v2\models\Schools;
use app\modules\v2\models\SchoolTeachers;
use app\modules\v2\models\SchoolTopic;
use app\modules\v2\models\StudentDetails;
use app\modules\v2\models\StudentSchool;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\TeacherClassSubjects;
use app\modules\v2\models\UserModel;
use Yii;
use app\modules\v2\models\{Classes,
    ApiResponse,
    Homeworks,
    TeacherClass,
    User,
    SearchSchool,
    StudentProfile,
    SubjectTopics,
    Questions
};
use yii\data\ActiveDataProvider;
use yii\db\Expression;
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
                CustomHttpBearerAuth::className(),
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

        if ($class->school->teacher_auto_join_class == 0) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Contact school admin to join');
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


        $classModel = TeacherClass::find()
            ->alias('tc')
            ->select([
                'c.class_name',
                'c.class_code',
                'tc.class_id'
            ])
            ->innerJoin('classes c', 'c.id = tc.class_id')
            ->where(['tc.id' => $model->id, 'status' => 1])
            ->asArray()
            ->one();

        return (new ApiResponse)->success(array_merge($classModel, ['subjects' => []]), ApiResponse::SUCCESSFUL, 'Teacher added successfully');
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

        //$notification = new InputNotification();
        //$notification->NewNotification('teacher_assigned_class', [['teacher_id', Yii::$app->user->id]]);


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
            return (new ApiResponse)->success(null,ApiResponse::SUCCESSFUL, 'Records not found');
        }

        return (new ApiResponse)->success($data, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionDeleteStudent($student_id, $class_id)
    {

        $type = Yii::$app->user->identity->type;
        $teacherStatus = $type == 'teacher' && TeacherClass::find()->where(['class_id' => $class_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1])->exists();
        $schoolStatus = $type == 'school' && StudentSchool::find()->where(['student_id' => $student_id, 'class_id' => $class_id, 'school_id' => Utility::getSchoolAccess()])->exists();
        if ((!$teacherStatus || !$schoolStatus) && !in_array($type, ['teacher', 'school'])) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'You are making an invalid request');
        }

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
        $model = SchoolTeachers::find()->select('school_id')->where(['teacher_id' => $teacher_id, 'status' => 1])->one();
        return $model->school_id;
    }

    public function actionAddStudent()
    {

        $form = new AddStudentForm;
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        //Disable subscription
//        $school_student_limit = Pricing::SubscriptionStatus(null, null, false);
//        if ($school_student_limit['unused_student'] < 1) {
//            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Students limit exceeded');
//        }
//        if (count($form->students) > $school_student_limit['unused_student']) {
//            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Not enough room to add new students');
//        }


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
        $schoolID = $this->SchoolID(Yii::$app->user->id);
        $curriculumStatus = Utility::SchoolActiveCurriculum($schoolID, true);
        //$curriculumID = Utility::SchoolActiveCurriculum($schoolID);
        $class_id = Yii::$app->request->get('global_class_id');
        $subject_id = Yii::$app->request->get('subject_id');
        $form = new \yii\base\DynamicModel(compact('class_id', 'subject_id'));
        $form->addRule(['class_id', 'subject_id'], 'required');
        if (!$curriculumStatus) {
            $form->addRule(['class_id'], 'exist', ['targetClass' => SubjectTopics::className(), 'targetAttribute' => ['class_id' => 'class_id']]);
        }
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR, 'Validation failed');
        }

        if ($term == 1) {
            $terms = ['first', 'second', 'third'];
            $model = [];
            foreach ($terms as $term) {
                if (!$curriculumStatus) {
                    $topics = SubjectTopics::find()
                        ->where(['subject_id' => $subject_id, 'class_id' => $class_id, 'term' => $term])
                        ->orderBy(['week_number' => SORT_ASC])
                        ->all();
                } else {
                    $class = Utility::SchoolAlternativeClass($class_id, false, $schoolID);
                    $topics = SchoolTopic::find()
                        ->where(['subject_id' => $subject_id, 'class_id' => $class, 'term' => $term])
                        ->orderBy(['position' => SORT_ASC])
                        ->groupBy('topic')
                        ->all();
                }
                $model[] = ['term' => $term, 'topics' => $topics];
            }
        } else {
            if (!$curriculumStatus) {
                $model = SubjectTopics::find()
                    ->where(['subject_id' => $subject_id, 'class_id' => $class_id])
                    ->orderBy(['term' => SORT_ASC, 'week_number' => SORT_ASC])
                    ->all();
            } else {
                $class = Utility::SchoolAlternativeClass($class_id, false, $schoolID);
                $model = SchoolTopic::find()
                    ->where(['subject_id' => $subject_id, 'class_id' => $class])
                    ->orderBy(['term' => SORT_ASC, 'week' => SORT_ASC])
                    ->groupBy('topic')
                    ->all();;
            }

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
        $schoolID = $this->SchoolID(Yii::$app->user->id);
        $curriculumStatus = Utility::SchoolActiveCurriculum($schoolID, true);
        if (!$curriculumStatus) {
            $model = SubjectTopics::find()
                ->where(['class_id' => $class_id, 'subject_id' => $subject_id])
                ->andWhere(['like', 'topic', '%' . $topic . '%', false])
                ->limit(6)
                ->all();
        } else {
            $class = Utility::SchoolAlternativeClass($class_id, false, $schoolID);
            $model = SchoolTopic::find()
                ->where(['class_id' => $class, 'subject_id' => $subject_id])
                ->andWhere(['like', 'topic', '%' . $topic . '%', false])
                ->groupBy('topic')
                ->limit(6)
                ->all();
        }

        if (!$model) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Record not found');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Record found');
    }

    public function actionClassDetails($class_id)
    {
        $teacherClass = TeacherClass::findOne(['class_id' => $class_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1]);
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

    public function actionGroupClasses($class_id = null)
    {
        if (Yii::$app->user->identity->type == 'school')
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        else {
            if (empty($class_id)) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class_id must be provided');
            }
            $school = TeacherClass::findOne(['class_id' => $class_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1]);
            $school = Schools::findOne(['id' => $school->school_id]);
        }

        $globalClasses = Utility::getMyGlobalClassesID($school->school_type);
        $classes = [];

        foreach ($globalClasses as $class) {
            $globalTemp = Utility::getGlobalClasses($class->id, $school);
            $classes[] = array_merge($globalTemp, ['classes' => $class->getSchoolClasses($school->id)]);
        }

        return (new ApiResponse)->success($classes, ApiResponse::SUCCESSFUL);
    }

    public function actionUpdateClass()
    {
        $student_id = Yii::$app->request->post('student_id');
        $class_id = Yii::$app->request->post('class_id');

        if (Yii::$app->user->identity->type == 'school') {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            $school_id = $school->id;
        } else {
            if (empty($class_id)) {
                return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Class_id must be provided');
            }
            $classes = Classes::findOne(['id' => $class_id]);
            if (!TeacherClass::find()->where(['school_id' => $classes->school_id, 'teacher_id' => Yii::$app->user->id, 'status' => 1])->exists()) {
                return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'You cannot move to this class.');
            }
            $school_id = $classes->school_id;
        }


        $model = new \yii\base\DynamicModel(compact('student_id', 'class_id', 'school_id'));
        $model->addRule(['student_id', 'class_id'], 'required');
        $model->addRule(['student_id'], 'exist', ['targetClass' => StudentSchool::className(), 'targetAttribute' => ['student_id', 'school_id']]);
        $model->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['school_id', 'class_id' => 'id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        $model = StudentSchool::findOne(['student_id' => $student_id, 'school_id' => $school_id, 'is_active_class' => 1, 'status' => 1]);
        $model->class_id = $class_id;
        if ($model->save())
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Student class updated');
        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not save');
    }


    /**
     * This fetches all teacher classes and subjects to each class
     * @return ApiResponse
     */
    public function actionClassesSubjects($teacher_id = null)
    {
        $session = [
            ["slug" => "2019-2020", 'name' => '2019/2020'],
            ["slug" => "2020-2021", 'name' => '2020/2021'],
            ["slug" => "2021-2022", 'name' => '2021/2022'],
        ];

        $user = Yii::$app->user->identity;
        if (!empty($teacher_id) && $user->type == 'school') {
            if (!TeacherClass::find()->where(['school_id' => Utility::getSchoolAccess(), 'teacher_id' => $teacher_id, 'status' => 1])->exists())
                return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, "Teacher not found");
        } else {
            $teacher_id = $user->id;
        }

        $classesSubject = [];
        $classes = TeacherClass::find()
            ->alias('tc')
            ->select([
                'c.class_name',
                'c.class_code',
                'c.global_class_id',
                'tc.class_id',
                'c.school_id'
            ])
            ->innerJoin('classes c', 'c.id = tc.class_id')
            ->where(['tc.teacher_id' => $teacher_id, 'status' => 1])
            ->groupBy('tc.class_id')
            ->asArray()
            ->all();

        $subjects = TeacherClassSubjects::find()
            ->alias('tcs')
            ->select([
                "IFNULL(ss.custom_subject_name,s.name) as name",
                's.id subject_id',
                's.slug',
                'tcs.class_id'
            ])
            ->innerJoin('subjects s', 's.id = tcs.subject_id')
            ->leftJoin('school_subject ss',"ss.subject_id = s.id AND ss.school_id = tcs.school_id")
            ->where(['tcs.teacher_id' => $teacher_id, 'tcs.status' => 1])
            ->asArray()
            ->all();

        foreach ($classes as $class) {
            $subjectList = [];
            foreach ($subjects as $subject) {
                if ($class['class_id'] == $subject['class_id']) {
                    $subjectList[] = $subject;
                }
            }
            $classesSubject[] = array_merge($class, ['subjects' => $subjectList]);
        }

        $response = ['classes' => $classesSubject, 'sessions' => $session];

        return (new ApiResponse)->success($response, ApiResponse::SUCCESSFUL);
    }
}
