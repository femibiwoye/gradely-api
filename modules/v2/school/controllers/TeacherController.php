<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\models\Classes;
use app\modules\v2\models\SchoolSubject;
use app\modules\v2\models\StudentDetails;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\TeacherClassSubjects;
use app\modules\v2\models\TeacherProfile;
use Yii;
use app\modules\v2\models\{User, ApiResponse, SchoolTeachers, Schools, UserModel, StudentSchool};
use app\modules\v2\components\{SharedConstant, Utility};
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

    public function actionIndex($class_id = null)
    {
        if ($class_id) {
            $school_id = Utility::getSchoolAccess()[0];
            $model = new \yii\base\DynamicModel(compact('class_id', 'school_id'));
            $model->addRule(['class_id'], 'exist', ['targetClass' => StudentSchool::className(), 'targetAttribute' => ['class_id' => 'class_id', 'school_id' => 'school_id']]);
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
            }

            $model = UserModel::find()
                ->innerJoin('teacher_class', 'teacher_class.teacher_id = user.id')
                ->where(['user.type' => 'teacher', 'teacher_class.class_id' => $class_id])
                ->with(['teacherClassesList', 'teacherSubjectList'])->groupBy(['id']);
        } else {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            $teachersID = SchoolTeachers::find()->where(['school_id' => $school->id, 'status' => 1])->all();
            $model = UserModel::find()->where(['type' => 'teacher', 'id' => ArrayHelper::getColumn($teachersID, 'teacher_id')])
                ->with(['teacherClassesList', 'teacherSubjectList'])
                ->groupBy(['id']);
        }

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
            'pagination' => ['pageSize' => 20]
        ]);

        return (new ApiResponse)->success($teachers->getModels(), null, null, $teachers);
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

    public function actionProfile($teacher_id)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('teacher_id', 'school_id'));
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => SchoolTeachers::className(), 'targetAttribute' => ['teacher_id' => 'teacher_id', 'school_id' => 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION, 'This teacher does not belong to your school');
        }

        $detail = TeacherProfile::findOne(['id' => $teacher_id]);
        return (new ApiResponse)->success($detail);
    }

    //Assign subjects only to teacher in a class
    public function actionAssignSubject()
    {
        $subjects = Yii::$app->request->post('subjects');
        $class_id = Yii::$app->request->post('class_id');
        $teacher_id = Yii::$app->request->post('teacher_id');
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id', 'subjects', 'teacher_id'));
        $model->addRule(['class_id', 'school_id', 'subjects', 'teacher_id'], 'required');
        $model->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id', 'school_id' => 'school_id']]);
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => SchoolTeachers::className(), 'targetAttribute' => ['teacher_id', 'school_id']]);
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!is_array($subjects)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'subjects must be an array');
        }

        return ['choice'=>$subjects,'list'=>SchoolSubject::find()->where(['school_id' => $school_id])->all()];

        if (count($subjects) > SchoolSubject::find()->where(['subject_id' => $subjects, 'school_id' => $school_id])->count()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'One or more subject is not in your subject list.');
        }

        $count = 0;
        foreach ($subjects as $subject) {
            if (TeacherClassSubjects::find()->where(['class_id' => $class_id, 'subject_id' => $subject, 'teacher_id' => $teacher_id])->exists())
                continue;

            $model = new TeacherClassSubjects();
            $model->class_id = $class_id;
            $model->teacher_id = $teacher_id;
            $model->subject_id = $subject;
            $model->school_id = $school_id;
            if ($model->save())
                $count = $count + 1;
        }
        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, $count > 0 ? $count . ' new subject assigned to teacher' : 'No new subject assigned');

    }

    //Assign teacher to class with subjects
    public function actionAssignClassSubject()
    {
        $subjects = Yii::$app->request->post('subjects');
        $class_id = Yii::$app->request->post('class_id');
        $teacher_id = Yii::$app->request->post('teacher_id');
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id', 'subjects', 'teacher_id'));
        $model->addRule(['class_id', 'school_id', 'subjects', 'teacher_id'], 'required');
        $model->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id', 'school_id' => 'school_id']]);
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => SchoolTeachers::className(), 'targetAttribute' => ['teacher_id', 'school_id']]);
        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (!is_array($subjects)) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'subjects must be an array');
        }

        if (count($subjects) > SchoolSubject::find()->where(['subject_id' => $subjects, 'school_id' => $school_id])->count()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'One or more subject is not in your subject list.');
        }


        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            if (!TeacherClass::find()->where(['teacher_id' => $teacher_id, 'school_id' => $school_id, 'class_id' => $class_id])->exists()) {
                $teacherClass = new TeacherClass();
                $teacherClass->teacher_id = $teacher_id;
                $teacherClass->class_id = $class_id;
                $teacherClass->school_id = $school_id;
                if (!$teacherClass->save())
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not add teacher to class');
            }

            foreach ($subjects as $subject) {
                if (TeacherClassSubjects::find()->where(['class_id' => $class_id, 'subject_id' => $subject, 'teacher_id' => $teacher_id])->exists())
                    continue;

                $model = new TeacherClassSubjects();
                $model->class_id = $class_id;
                $model->teacher_id = $teacher_id;
                $model->subject_id = $subject;
                $model->school_id = $school_id;
                if (!$model->save())
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not add one or more subjects');

            }


            $dbtransaction->commit();
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }

        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Successful');

    }

    //Remove teacher from class
    public function actionRemoveTeacher()
    {
        $class_id = Yii::$app->request->post('class_id');
        $teacher_id = Yii::$app->request->post('teacher_id');
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('class_id', 'school_id', 'teacher_id'));
        $model->addRule(['class_id', 'school_id', 'teacher_id'], 'required');
        $model->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id', 'school_id' => 'school_id']]);
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => TeacherClass::className(), 'targetAttribute' => ['teacher_id', 'school_id', 'class_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = TeacherClass::findOne(['school_id' => $school_id, 'class_id' => $class_id, 'teacher_id' => $teacher_id]);

        if ($model->delete()) {
            TeacherClassSubjects::deleteAll(['teacher_id' => $model->teacher_id, 'class_id' => $model->class_id, 'school_id' => $model->school_id]);
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Successful');
        }

        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not remove teacher from class');
    }

    //Remove teacher from class
    public function actionRemoveTeacherSchool()
    {
        $teacher_id = Yii::$app->request->post('teacher_id');
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('school_id', 'teacher_id'));
        $model->addRule(['school_id', 'teacher_id'], 'required');
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => SchoolTeachers::className(), 'targetAttribute' => ['teacher_id', 'school_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        $model = SchoolTeachers::findOne(['school_id' => $school_id, 'teacher_id' => $teacher_id]);

        if ($model->delete()) {
            TeacherClass::deleteAll(['teacher_id' => $model->teacher_id, 'school_id' => $model->school_id]);
            TeacherClassSubjects::deleteAll(['teacher_id' => $model->teacher_id, 'school_id' => $model->school_id]);
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Successful');
        }

        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not remove teacher from class');
    }
}

