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
            [['tutor_id', 'verified', 'satisfaction', 'availability'], 'integer'],
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
            'availability' => 'Availability',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'tutor_id',
            'verified',
            'video_sample',
            'language_level',
            'satisfaction',
            'experience',
            'availability',
            'created_at',
            'updated_at',
            'tutor',
            'curriculum',
            'availability',
            'calender',
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

    public function getAvailability()
    {
        return $this->hasOne(TutorAvailability::className(), ['user_id' => 'tutor_id']);
    }

    public function getTutorSubject()
    {
        return $this->hasMany(TutorSubject::className(), ['tutor_id' => 'tutor_id']);
    }

    public function getCalender()
    {
        return [
            'date' => date('m/d/Y', strtotime($this->tutorSession->availability)),
            'time' => date('H:i:s A', strtotime($this->tutorSession->availability)),
        ];
    }

    public function getTutorSession()
    {
        return TutorSession::findOne(['requester_id' => $this->tutor_id]);
    }

    public function getReview()
    {
        return $this->hasMany(Review::className(), ['receiver_id' => 'tutor_id']);
    }

    public function getTutorReview()
    {
        return $this->getReview()->where(['receiver_type' => 'tutor']);
    }

    public function getSumRating()
    {
        $sum_rating = 0;
        foreach ($this->tutorReview as $review) {
            $sum_rating = $sum_rating + $review->rate;
        }

        return $sum_rating;
    }

    public function getCurriculum()
    {
        $subjects = $this->getTutorSubject();
        if (Yii::$app->request->get('subject'))
            $subjects = $subjects->where(['slug' => Yii::$app->request->get('subject')]);
        $total_rating = count($this->tutorReview);
        $sum_rating = $total_rating > 0 ? $this->sumRating / $total_rating : 0;
        return [
            'subjects' => $subjects->all(),
            'rating' => $sum_rating,
            'total_rating' => $total_rating,
        ];
    }
}
