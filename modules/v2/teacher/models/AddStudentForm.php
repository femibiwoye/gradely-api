<?php

namespace app\modules\v2\teacher\models;

use app\modules\v2\components\InputNotification;
use app\modules\v2\components\Pricing;
use app\modules\v2\components\Utility;
use app\modules\v2\models\Schools;
use app\modules\v2\models\StudentSchool;
use Yii;
use yii\base\Model;
use app\modules\v2\models\{Classes, SignupForm, Parents, GenerateString, User, InviteLog};
use app\modules\v2\components\SharedConstant;
use yii\helpers\ArrayHelper;

/**
 * Password reset request form
 */
class AddStudentForm extends Model
{
    public $class_id;
    public $password;
    public $students;
    private $added_students = [];

    public function rules()
    {
        return [
            [['class_id', 'password', 'students'], 'required'],
            ['class_id', 'integer'],
            ['password', 'string', 'min' => 6],
            [['class_id'], 'exist', 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
            [['students'], 'validateStudents'],
        ];
    }

    public function validateStudents()
    {
        foreach ($this->students as $student) {
            $name_array = explode(' ', $student['name'], 2);
            $model = User::findAll(['firstname' => $name_array[0], 'lastname' => isset($name_array[1]) ? $name_array[1] : null]);

            if ($model && StudentSchool::find()->where(['class_id' => $this->class_id, 'student_id' => ArrayHelper::getColumn($model, 'id')])->exists()) {
                $this->addError($student['name'] . " is already added");
            }

            return true;
        }
    }

    public function addStudent($type, $student)
    {
        $form = new SignupForm(['scenario' => "$type-signup"]);
        $form->class = $this->class_id;
        $form->password = $this->password;
        $name_array = explode(' ', $student['name'], 2);
        $form->first_name = $name_array[0];
        $form->last_name = isset($name_array[1]) ? $name_array[1] : $name_array[0];
        $form->country = SharedConstant::DEFAULT_COUNTRY;

        if (!$form->validate()) {
            throw new \UnexpectedValueException($student['name'] . ' data is not validated!');
        }

        if (!$user = $form->signup($type)) {
            return false;
        }

        if (!$this->checkParent($student, $user)) {
            return false;
        }

        $notification = new InputNotification();
        $notification->NewNotification('teacher_add_student_student', [['student_id', $user->id]]);


        $this->addToClass($user);

        return $user;
    }

    /**
     * This add the student to class immediately teacher register the child
     * @param $user
     * @return bool
     */
    private function addToClass($user)
    {
        $model = new StudentSchool();
        $model->class_id = $this->class_id;
        $model->student_id = $user->id;
        $model->school_id = Classes::findOne(['id' => $this->class_id])->school_id;
        $model->status = 1;
        return $model->save();
    }

    public function addStudents($type)
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($this->students as $student) {

                $studentName = $student['name'];
                if (strpos($studentName, '/') !== false) { // Add existing student by code
                    if ($userModel = User::findOne(['code' => $studentName, 'type' => 'student'])) {
                        if (StudentSchool::find()->where(['student_id' => $userModel->id, 'is_active_class' => 1, 'status' => 1])->exists() || !$this->addToClass($userModel)) {
                            return false;
                        }
                        $student = $userModel;
                    }
                } elseif (!$student = $this->addStudent($type, $student)) { // Add new student
                    return false;
                }

                array_push($this->added_students, $student);
            }
            $studentsID = ArrayHelper::getColumn($this->added_students, 'id');
            Pricing::SchoolAddStudentSubscribe($studentsID);

            /********** Send notification start **********/
            $notification = new InputNotification();
            $notification->NewNotification('teacher_adds_student', [
                ['teacher_id', Yii::$app->user->id],
                ['students_id', implode(',', ArrayHelper::getColumn($this->added_students, 'id'))],
                ['password', $this->password],
                ['class_id', $this->class_id]
            ]);
            /********* Send notification end **********/



            $dbtransaction->commit();
        } catch (\Exception $e) {
            $dbtransaction->rollBack();
            return $this->addError('students', $e->getMessage());
        }

        return $this->added_students;
    }

    public function checkParent($student, $user)
    {

        if (isset($student['email']) && !empty($student['email'])) {
            $parent = User::find()->andWhere(['email' => $student['email']])->andWhere(['type' => SharedConstant::TYPE_PARENT])->one();

            if ($parent) {
                $model = new Parents;
                $model->student_id = $user->id;
                $model->parent_id = $parent->id;
                $model->code = $user->code;
                $model->inviter = SharedConstant::TYPE_STUDENT;
                $model->status = SharedConstant::VALUE_ONE;
                $model->invitation_token = GenerateString::widget();
                if (!$model->save(false)) {
                    return false;
                }

                $notification = new InputNotification();
                $notification->NewNotification('teacher_add_student_parent', [['parent_id', $parent->id]]);

            } else {
                $model = new InviteLog;
                $model->receiver_email = $student['email'];
                $model->receiver_type = SharedConstant::TYPE_PARENT;
                $model->receiver_phone = $student['phone'];
                $model->sender_type = SharedConstant::TYPE_STUDENT;
                $model->sender_id = $user->id;
                $model->token = GenerateString::widget();
                $model->status = SharedConstant::VALUE_ZERO;
                if (!$model->save(false)) {
                    return false;
                }

                if (Yii::$app->user->identity->type == 'school') {
                    $schoolName = Schools::findOne(['id' => Utility::getSchoolAccess(Yii::$app->user->id)]);
                } else {
                    $schoolName = Schools::findOne(['id' => Utility::getTeacherSchoolID(Yii::$app->user->id)]);
                }

                if ($schoolName) {

                    $notification = new InputNotification();
                    $notification->NewNotification('invite_parent', [
                        ['school_name', $schoolName->name],
                        ['invite_token', $model->token],
                        ['student_id', $model->sender_id],
                        ['child_name', $user->firstname],
                        ['email', $model->receiver_email]
                    ]);
                }

            }
        }

        return true;
    }
}
