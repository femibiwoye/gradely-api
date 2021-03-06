<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

/**
 * This is the model class for table "teacher_class".
 *
 * @property int $id
 * @property int $teacher_id
 * @property int $school_id
 * @property int $class_id
 * @property int $status
 * @property string $created_at
 *
 * @property Schools $school
 * @property User $teacher
 * @property Classes $class
 */
class TeacherClass extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'teacher_class';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['teacher_id', 'school_id', 'class_id'], 'required'],
            [['teacher_id', 'school_id', 'class_id', 'status'], 'integer'],
            ['status', 'default', 'value' => SharedConstant::VALUE_ZERO],
            [['created_at'], 'safe'],
            [['school_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schools::className(), 'targetAttribute' => ['school_id' => 'id']],
            [['teacher_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['teacher_id' => 'id']],
            [['class_id'], 'exist', 'skipOnError' => true, 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'teacher_id' => 'Teacher ID',
            'school_id' => 'School ID',
            'class_id' => 'Class ID',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[School]].
     *
     * @return \yii\db\ActiveQuery
     */

    public function getSchool()
    {
        return $this->hasOne(Schools::className(), ['id' => 'school_id']);
    }

    /**
     * Gets query for [[Teacher]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeacher()
    {
        return $this->hasOne(User::className(), ['id' => 'teacher_id']);
    }

    /**
     * Gets query for [[Class]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id']);
    }

    public function addSchoolTeacher($status = 0)
    {
        if (!SchoolTeachers::find()->where(['school_id' => $this->school_id, 'teacher_id' => $this->teacher_id])->exists()) {
            $model = new SchoolTeachers();
            $model->school_id = $this->school_id;
            $model->teacher_id = $this->teacher_id;
            $model->status = $status;
            $model->save();
        }
    }

    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->created_at = date('Y-m-d H:i:s');
        }

        return parent::beforeSave($insert);
    }
}
