<?php

namespace app\modules\v1\models;

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
}
