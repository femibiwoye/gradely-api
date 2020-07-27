<?php

namespace app\modules\v2\models;

use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "schools".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $slug
 * @property string|null $name
 * @property string $abbr
 * @property string|null $logo
 * @property string|null $banner
 * @property string|null $tagline
 * @property string|null $about
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $postal_code
 * @property string|null $website
 * @property string|null $establish_date
 * @property string|null $contact_name
 * @property string|null $contact_role
 * @property string|null $contact_email
 * @property string|null $contact_image
 * @property string|null $phone
 * @property string|null $phone2
 * @property string|null $school_email
 * @property string|null $school_type
 * @property string $naming_format SS is primary, junior and senior secondary school naming format. Yeah is for year1 to year12.
 * @property string|null $timezone
 * @property string|null $boarding_type
 * @property string $created_at
 *
 * @property ClassSubjects[] $classSubjects
 * @property Classes[] $classes
 * @property SchoolAdmin[] $schoolAdmins
 * @property SchoolCalendar[] $schoolCalendars
 * @property SchoolClassCurriculum[] $schoolClassCurriculums
 * @property SchoolCurriculum[] $schoolCurriculums
 * @property SchoolOptions[] $schoolOptions
 * @property SchoolSubject[] $schoolSubjects
 * @property SchoolTeachers[] $schoolTeachers
 * @property User $user
 * @property TeacherClass[] $teacherClasses
 */
class Schools extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'schools';
    }

    public function behaviors()
    {
        return [
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'name',
                'ensureUnique' => true
            ],
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'abbr',
                'ensureUnique' => true,
                'slugAttribute' => 'abbr'
            ]

        ];
    }

    public function rules()
    {
        return [
            //[['user_id', 'slug', 'abbr'], 'required'],
            [['user_id'], 'integer'],
            [['about', 'naming_format'], 'string'],
            [['created_at'], 'safe'],
            [['slug', 'name', 'logo', 'banner', 'tagline', 'address'], 'string', 'max' => 255],
            [['abbr'], 'string', 'max' => 10],
            [['city', 'state', 'country', 'website', 'contact_name', 'contact_email', 'contact_image', 'school_email', 'school_type'], 'string', 'max' => 100],
            [['postal_code', 'establish_date'], 'string', 'max' => 20],
            [['contact_role', 'timezone', 'boarding_type'], 'string', 'max' => 50],
            [['phone', 'phone2'], 'string', 'max' => 15],

            [['name', 'abbr'], 'required', 'on' => 'school_signup'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'slug' => 'Slug',
            'name' => 'Name',
            'abbr' => 'Abbr',
            'logo' => 'Logo',
            'banner' => 'Banner',
            'tagline' => 'Tagline',
            'about' => 'About',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'postal_code' => 'Postal Code',
            'website' => 'Website',
            'establish_date' => 'Establish Date',
            'contact_name' => 'Contact Name',
            'contact_role' => 'Contact Role',
            'contact_email' => 'Contact Email',
            'contact_image' => 'Contact Image',
            'phone' => 'School Phone',
            'phone2' => 'Contact Phone',
            'school_email' => 'School Email',
            'school_type' => 'School Type',
            'created_at' => 'Created At',
        ];
    }


    /**
     * Gets query for [[ClassSubjects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClassSubjects()
    {
        return $this->hasMany(ClassSubjects::className(), ['school_id' => 'id']);
    }

    /**
     * Gets query for [[Classes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClasses()
    {
        return $this->hasMany(Classes::className(), ['school_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolAdmins]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolAdmins()
    {
        return $this->hasMany(SchoolAdmin::className(), ['school_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolCalendars]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolCalendars()
    {
        return $this->hasMany(SchoolCalendar::className(), ['school_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolClassCurriculums]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolClassCurriculums()
    {
        return $this->hasMany(SchoolClassCurriculum::className(), ['school_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolCurriculums]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolCurriculums()
    {
        return $this->hasMany(SchoolCurriculum::className(), ['school_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolOptions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolOptions()
    {
        return $this->hasMany(SchoolOptions::className(), ['school_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolSubjects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolSubjects()
    {
        return $this->hasMany(SchoolSubject::className(), ['school_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolTeachers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolTeachers()
    {
        return $this->hasMany(SchoolTeachers::className(), ['school_id' => 'id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * Gets query for [[TeacherClasses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeacherClasses()
    {
        return $this->hasMany(TeacherClass::className(), ['school_id' => 'id']);
    }

}
