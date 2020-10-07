<?php

namespace app\modules\v2\models;

use Yii;
use yii\db\Expression;

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

    /*
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
                'ratings',
                'availability',
                'calender',
                'reviews' => 'review',
            ];
        }*/

    public function fields()
    {
        $fields = parent::fields();

        $fields['user'] = 'user';
        $fields['rating'] = 'rating';
        $fields['curriculum'] = 'curriculum';
        $fields['subject'] = 'subject';
        if ($this->isRelationPopulated('calendar')) {

            $fields['calendar'] = 'calendar';
            $fields['reviews'] = 'reviews';
        }


        return $fields;
    }

    /**
     * Gets query for [[Tutor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'tutor_id']);
    }

    public function getAvailability()
    {
        return $this->hasOne(TutorAvailability::className(), ['user_id' => 'tutor_id']);
    }

    public function getTutorSubject()
    {
        return Subjects::find()
            ->innerJoin('tutor_subject', 'tutor_subject.subject_id = subjects.id')
            ->where(['tutor_subject.tutor_id' => $this->tutor_id])
            ->all();

    }

    public function getCalender()
    {
        return [
            'date' => date('Y-m-d', strtotime($this->tutorSession->availability)),
            'time' => date('H:i:s A', strtotime($this->tutorSession->availability)),
        ];
    }

    public function getTutorSession()
    {
        return TutorSession::findOne(['requester_id' => $this->tutor_id]);
    }

    public function getReviews()
    {
        return $this->hasMany(Review::className(), ['receiver_id' => 'tutor_id']);
    }


    public function getRating()
    {
        $rate =  Review::find()->select([
            new Expression('ROUND(SUM(rate)/count(id),1) as rate'),
        ])
            ->where(['receiver_id' => $this->tutor_id])
            ->asArray()
            ->one();

        return $rate["rate"];
    }

    public function getTutorCurriculum()
    {
        return $this->hasMany(TutorSubject::className(), ['tutor_id' => 'tutor_id'])
            ->groupBy('curriculum_id');
    }

    public function getTutorSubjects()
    {
        return $this->hasMany(TutorSubject::className(), ['tutor_id' => 'tutor_id'])
            ->groupBy('subject_id');
    }

    public function getCurriculum()
    {
        return $this->hasMany(ExamType::className(), ['id' => 'curriculum_id'])
            ->select(['id', 'slug', 'name', 'title', 'description'])
            ->via('tutorCurriculum');
    }

    public function getSubject()
    {
        return $this->hasMany(Subjects::className(), ['id' => 'subject_id'])
            ->via('tutorSubjects');
    }

    public function getCalendar()
    {
        return $this->hasMany(TutorAvailability::className(), ['user_id' => 'tutor_id'])
            ->andWhere(['status'=>1]);
    }
}
