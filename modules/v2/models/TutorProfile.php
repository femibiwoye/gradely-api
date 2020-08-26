<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "tutor_profile".
 *
 * @property int $id
 * @property int $tutor_id
 * @property int|null $verified
 * @property string|null $video_sample
 * @property string|null $language_level
 * @property int|null $satisfaction
 * @property string|null $experience
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property User $tutor
 */
class TutorProfile extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tutor_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tutor_id'], 'required'],
            [['tutor_id', 'verified', 'satisfaction'], 'integer'],
            [['video_sample'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['language_level', 'experience'], 'string', 'max' => 50],
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
            'verified' => 'Verified',
            'video_sample' => 'Video Sample',
            'language_level' => 'Language Level',
            'satisfaction' => 'Satisfaction',
            'experience' => 'Experience',
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
