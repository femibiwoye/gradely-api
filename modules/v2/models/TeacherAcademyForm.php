<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "teacher_academy_form".
 *
 * @property int $id
 * @property string $type
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string|null $school_name
 * @property string|null $role
 * @property int|null $teacher_count
 * @property int|null $payment_status
 * @property string|null $paid_at
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class TeacherAcademyForm extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'teacher_academy_form';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'name', 'email', 'phone'], 'required'],
            [['type'], 'string'],
            [['teacher_count', 'payment_status'], 'integer'],
            [['paid_at', 'created_at', 'updated_at'], 'safe'],
            [['name', 'school_name', 'role'], 'string', 'max' => 100],
            [['email'], 'string', 'max' => 50],
            [['phone'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'school_name' => 'School Name',
            'role' => 'Role',
            'teacher_count' => 'Teacher Count',
            'payment_status' => 'Payment Status',
            'paid_at' => 'Paid At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
