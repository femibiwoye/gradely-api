<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "parents".
 *
 * @property int $id
 * @property int $parent_id
 * @property int $student_id
 * @property string|null $code
 * @property string|null $inviter
 * @property int $status
 * @property string $role
 * @property string|null $invitation_token
 * @property string $created_at
 */
class Parents extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'parents';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parent_id', 'student_id'], 'required'],
            [['parent_id', 'student_id', 'status'], 'integer'],
            [['created_at'], 'safe'],
            [['code'], 'string', 'max' => 100],
            [['inviter', 'role'], 'string', 'max' => 20],
            [['invitation_token'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent ID',
            'student_id' => 'Student ID',
            'code' => 'Code',
            'inviter' => 'Inviter',
            'status' => 'Status',
            'role' => 'Role',
            'invitation_token' => 'Invitation Token',
            'created_at' => 'Created At',
        ];
    }

    public function getStudentProfile()
    {
        return $this->hasOne(UserModel::className(), ['id' => 'student_id']);
    }

    public function getParentProfile()
    {
        return $this->hasOne(UserModel::className(), ['id' => 'parent_id']);
    }

    public function getStudentClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id'])->via('studentSchool');
    }

    public function getStudentSchool()
    {
        return $this->hasOne(StudentSchool::className(), ['student_id' => 'student_id'])->andWhere(['student_school.status' => 1, 'student_school.is_active_class' => 1]);
    }

    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->created_at = date('Y-m-d H:i:s');;
        }

        return parent::beforeSave($insert);
    }
}