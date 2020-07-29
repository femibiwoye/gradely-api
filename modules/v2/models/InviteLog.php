<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "invite_log".
 *
 * @property int $id
 * @property string $receiver_email
 * @property string|null $receiver_name
 * @property string $receiver_type
 * @property string|null $receiver_phone
 * @property int|null $receiver_class
 * @property int|null $receiver_subject
 * @property string|null $receiver_subjects Used to invite teacher to multiple subjects
 * @property string $sender_type
 * @property int $sender_id
 * @property string $token
 * @property string|null $extra_data
 * @property int $status
 * @property string $created_at
 */
class InviteLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invite_log';
    }

    const SCENARIO_SCHOOL_INVITE_ADMIN = 'invite-school-admin';
    const SCENARIO_SCHOOL_INVITE_TEACHER = 'invite-school-teacher';

    public $role;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['receiver_email', 'receiver_type', 'sender_type', 'sender_id', 'token'], 'required'],
            [['receiver_class', 'receiver_subject', 'sender_id', 'status'], 'integer'],
            [['extra_data'], 'string'],
            [['created_at'], 'safe'],
            [['receiver_email', 'receiver_name'], 'string', 'max' => 100],
            [['receiver_type', 'sender_type'], 'string', 'max' => 20],
            [['receiver_phone'], 'string', 'max' => 50],
            [['token'], 'string', 'max' => 200],

            [['receiver_email', 'receiver_name', 'receiver_phone', 'role'], 'required', 'on' => self::SCENARIO_SCHOOL_INVITE_ADMIN],
            ['receiver_email', 'email', 'on' => self::SCENARIO_SCHOOL_INVITE_ADMIN],

            [['receiver_email', 'receiver_name', 'receiver_phone', 'receiver_class', 'receiver_subjects'], 'required', 'on' => self::SCENARIO_SCHOOL_INVITE_TEACHER],
            ['receiver_email', 'email', 'on' => self::SCENARIO_SCHOOL_INVITE_TEACHER],
            ['receiver_email', 'validateRepetetion', 'on' => self::SCENARIO_SCHOOL_INVITE_TEACHER],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'receiver_email' => 'Receiver Email',
            'receiver_name' => 'Receiver Name',
            'receiver_type' => 'Receiver Type',
            'receiver_phone' => 'Receiver Phone',
            'receiver_class' => 'Receiver Class',
            'receiver_subject' => 'Receiver Subject',
            'receiver_subjects' => 'Receiver Subjects',
            'sender_type' => 'Sender Type',
            'sender_id' => 'Sender ID',
            'token' => 'Token',
            'extra_data' => 'Extra Data',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    public function schoolInviteAdmin($school)
    {
        $model = new InviteLog();
        $model->receiver_name = $this->receiver_email;
        $model->receiver_email = $this->receiver_email;
        $model->receiver_phone = $this->receiver_phone;
        $model->sender_id = $school->id;
        $model->sender_type = 'school';
        $model->receiver_type = 'school';
        $model->extra_data = $this->role;
        $model->token = Yii::$app->security->generateRandomString(100);
        if ($model->save())
            return $model;
        return false;
    }

    public function validateRepetetion()
    {
        if (InviteLog::find()->where(['sender_id' => $this->sender_id, 'receiver_email' => $this->receiver_email])->exists()) {
            $this->addError('receiver_email', 'Invitation has already been sent to teacher');
            return false;
        }
    }

    public function schoolInviteTeacher()
    {
        $model = new InviteLog();
        $model->receiver_name = $this->receiver_email;
        $model->receiver_email = $this->receiver_email;
        $model->receiver_phone = $this->receiver_phone;
        if (is_array($this->receiver_subjects))
            $model->receiver_subjects = json_encode($this->receiver_subjects);
        else
            $model->receiver_subject = $this->receiver_subjects;
        $model->sender_id = $this->sender_id;
        $model->sender_type = 'school';
        $model->receiver_type = 'teacher';
        $model->token = Yii::$app->security->generateRandomString(100);
        if ($model->save())
            return $model;
        return false;
    }

    public function schoolReinviteTeacher($id)
    {
        if ($id) {
            $schoolID = Schools::find()
                ->where(['user_id' => Yii::$app->user->id])
                ->orderBy('id DESC')
                ->one();
            $model = InviteLog::find()->where(['sender_id' => $schoolID->id, 'id' => $id])->one();
            if (!empty($model)) {
                $this->sendEmail($model, $schoolID);
                $this->sendSMS($model, $schoolID);
                Yii::$app->session->setFlash('info', 'Invitation has been sent.');
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }
}
