<?php

namespace app\modules\v2\school\controllers;

use app\modules\v2\components\CustomHttpBearerAuth;
use app\modules\v2\models\ClassSubjects;
use app\modules\v2\models\InviteLog;
use app\modules\v2\models\SchoolSubject;
use app\modules\v2\models\Subjects;
use app\modules\v2\models\TeacherClass;
use app\modules\v2\models\TeacherClassSubjects;
use app\modules\v2\models\TeacherProfile;
use Yii;
use app\modules\v2\models\{User, ApiResponse, SchoolTeachers, Schools, UserModel, StudentSchool, Classes};
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


    public function actionIndex($class_id = null)
    {
        if ($class_id) {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            $school_id = $school->id;
            $model = new \yii\base\DynamicModel(compact('class_id', 'school_id'));
            $model->addRule(['class_id'], 'exist', ['targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id', 'school_id' => 'school_id']]);
            if (!$model->validate()) {
                return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
            }

            $model = UserModel::find()
                ->innerJoin('teacher_class', 'teacher_class.teacher_id = user.id AND teacher_class.status=1')
                ->innerJoin('school_teachers', 'school_teachers.teacher_id = user.id AND school_teachers.school_id = ' . $school_id . ' AND school_teachers.status=1')
                ->where(['user.type' => 'teacher', 'teacher_class.class_id' => $class_id])
                ->with(['teacherClassesList', 'teacherSubjectList'])->groupBy(['user.id']);
        } else {
            $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
            $teachersID = SchoolTeachers::find()->where(['school_id' => $school->id, 'status' => 1])->all();
            $model = UserModel::find()->where(['type' => 'teacher', 'user.id' => ArrayHelper::getColumn($teachersID, 'teacher_id')])
                ->innerJoin('school_teachers', 'school_teachers.teacher_id = user.id AND school_teachers.school_id = ' . $school->id . ' AND school_teachers.status=1')
                ->with(['teacherClassesList', 'teacherSubjectList'])
                ->groupBy(['user.id']);
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
            if ($model->save()) {
                TeacherClass::updateAll(['status' => 1], ['teacher_id' => $id, 'status' => 0, 'school_id' => $school->id]);
                return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Teacher accepted!');
            }
        }

        if ($id == 0) {
            if (SchoolTeachers::updateAll(['status' => 1], ['school_id' => $school->id, 'status' => 0])) {
                TeacherClass::updateAll(['status' => 1], ['status' => 0, 'school_id' => $school->id]);
                return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Pending teachers has been accepted!');
            }
        }

        return (new ApiResponse)->success(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Invalid requests');
    }

    public function actionDeclineTeacher($id = null)
    {
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);

        if ($id > 0 && $model = SchoolTeachers::findOne(['teacher_id' => $id, 'school_id' => $school->id, 'status' => 0])) {
            $model->delete();
            TeacherClass::deleteAll(['teacher_id' => $id, 'school_id' => $school->id]);
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Teacher declined & removed!');
        }

        if ($id == 0) {
            $ids = ArrayHelper::getColumn(SchoolTeachers::find()->where(['school_id' => $school->id, 'status' => 0])->all(), 'teacher_id');
            if (SchoolTeachers::deleteAll(['school_id' => $school->id, 'teacher_id' => $ids, 'status' => 0])) {
                TeacherClass::deleteAll(['teacher_id' => $ids, 'school_id' => $school->id]);
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
            $model->status = 1;
            if ($model->save())
                $count = $count + 1;

            $this->AddClassSubject($subject, $class_id, $school_id);
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
                $teacherClass->status = 1;
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
                $model->status = 1;
                if (!$model->save())
                    return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not add one or more subjects');

                $this->AddClassSubject($subject, $class_id, $school_id);
            }


            $dbtransaction->commit();
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }


        return $this->actionIndex($class_id);
//        return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Successful');

    }

    public function AddClassSubject($subject, $class, $school)
    {
        if (ClassSubjects::find()->where(['subject_id' => $subject, 'class_id' => $class, 'school_id' => $school])->exists())
            return true;

        $model = new ClassSubjects();
        $model->class_id = $class;
        $model->school_id = $school;
        $model->subject_id = $subject;
        $model->status = 1;
        return $model->save();

    }

    public function actionPopulateClassSubjects()
    {
        foreach (Schools::find()->all() as $school) {
            foreach (TeacherClassSubjects::find()->where(['school_id' => $school->id, 'status' => 1])->groupBy('class_id')->all() as $class) {
                foreach (TeacherClassSubjects::find()->where(['school_id' => $school->id, 'class_id' => $class->class_id, 'status' => 1])->groupBy('subject_id')->all() as $subjects) {
                    $this->AddClassSubject($subjects->subject_id, $class->class_id, $school->id);
                }
            }
        }


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

    /**
     * Remove subject from teacher in a class.
     * @return ApiResponse
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionRemoveTeacherSubject()
    {
        $teacher_id = Yii::$app->request->post('teacher_id');
        $class_id = Yii::$app->request->post('class_id');
        $subject_id = Yii::$app->request->post('subject_id');
        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $school_id = $school->id;
        $model = new \yii\base\DynamicModel(compact('school_id', 'teacher_id', 'class_id', 'subject_id'));
        $model->addRule(['school_id', 'teacher_id', 'class_id', 'subject_id'], 'required');
        $model->addRule(['teacher_id'], 'exist', ['targetClass' => TeacherClassSubjects::className(), 'targetAttribute' => ['teacher_id', 'school_id', 'class_id']]);

        if (!$model->validate()) {
            return (new ApiResponse)->error($model->getErrors(), ApiResponse::UNABLE_TO_PERFORM_ACTION);
        }

        if (TeacherClassSubjects::deleteAll(['school_id' => $school_id, 'teacher_id' => $teacher_id, 'class_id' => $class_id, 'subject_id' => $subject_id])) {
            return (new ApiResponse)->success(null, ApiResponse::SUCCESSFUL, 'Successful');
        }

        return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Could not remove subject from teacher');
    }

    public function actionPendingInvitation()
    {

        $school = Schools::findOne(['id' => Utility::getSchoolAccess()]);
        $invites = InviteLog::find()
            ->where(['sender_type' => 'school', 'sender_id' => $school->id, 'invite_log.status' => 0, 'receiver_type' => 'teacher'])
            ->asArray()
            ->all();

        foreach ($invites as $index => $invite) {
            $invites[$index] = array_merge($invite, ['subjects' => Subjects::find()->where(['id' => json_decode($invite['receiver_subjects'])])->all()]);
        }


        return (new ApiResponse)->success($invites, null, null);
    }
}

