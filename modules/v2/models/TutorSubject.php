<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "tutor_subject".
 *
 * @property int $id
 * @property int $tutor_id
 * @property int|null $curriculum_id
 * @property int|null $subject_id
 * @property int|null $class
 * @property int $status
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property User $tutor
 */
class TutorSubject extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tutor_subject';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tutor_id'], 'required'],
            [['tutor_id', 'curriculum_id', 'subject_id', 'class', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['tutor_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['tutor_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tutor_id' => 'Tutor ID',
            'curriculum_id' => 'Curriculum ID',
            'subject_id' => 'Subject ID',
            'class' => 'Class',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Tutor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTutor()
    {
        return $this->hasOne(User::className(), ['id' => 'tutor_id']);
    }
}
