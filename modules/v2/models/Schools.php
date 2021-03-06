<?php

namespace app\modules\v2\models;

use app\modules\v2\components\Utility;
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
 * @property string|null $wallpaper
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
 * @property string|null $subscription_expiry
 * @property string|null $subscription_plan
 * @property string|null $basic_subscription
 * @property string|null $premium_subscription
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
                'ensureUnique' => true,
                'immutable' => true
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
            [['about', 'naming_format', 'subscription_plan'], 'string'],
            [['created_at', 'subscription_plan', 'subscription_plan'], 'safe'],
            [['slug', 'name', 'logo', 'banner', 'tagline', 'address', 'contact_image'], 'string', 'max' => 255],
            [['abbr'], 'string', 'max' => 10],
            [['subscription_expiry'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['city', 'state', 'country', 'website', 'contact_name', 'contact_email', 'school_email', 'school_type'], 'string', 'max' => 100],
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
            'subscription_plan' => 'Subscription Plan',
            'subscription_expiry' => 'Subscription Expiry',
            'created_at' => 'Created At',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'user_id',
            'slug',
            'name',
            'abbr',
            'logo' => function () {
                return Utility::getGeneralImage($this->logo, 'schools');
            },
            'banner' => function () {
                return Utility::getGeneralImage($this->banner, 'schools');
            },
            'wallpaper',
            'tagline',
            'about',
            'address',
            'city',
            'state',
            'country',
            'postal_code',
            'website',
            'establish_date',
            'contact_name',
            'contact_role',
            'contact_email',
            'contact_image',
            'phone',
            'phone2',
            'school_email',
            'school_type',
            'boarding_type',
            'timezone',
            'created_at',
            'curriculum' => 'schoolCurriculumDetails',
            'demographics' => 'demographics',
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

//    /**
//     * Gets query for [[SchoolClassCurriculums]].
//     *
//     * @return \yii\db\ActiveQuery
//     */
//    public function getSchoolClassCurriculums()
//    {
//        return $this->hasMany(SchoolClassCurriculum::className(), ['school_id' => 'id']);
//    }

    /**
     * Gets query for [[SchoolCurriculums]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolCurriculums()
    {
        return $this->hasMany(SchoolCurriculum::className(), ['school_id' => 'id']);
    }

    public function getSchoolCurriculumDetails()
    {
        return $this->hasMany(ExamType::className(), ['id' => 'curriculum_id'])->select(['id', 'slug', 'name', 'title', 'description'])->via('schoolCurriculums');
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

    public function getDemographics()
    {
        $model = UserProfile::find()->where(['user_id' => $this->user_id]);
        $total = count($model->all());
        $gender = [
            'male' => $total > 0 ? count($model->andWhere(['gender' => 'male'])->all()) * 100 / $total : 0,
            'female' => $total > 0 ? count($model->andWhere(['gender' => 'female'])->all()) * 100 / $total : 0,
        ];

        $age = [
            '7-10' => $total > 0 ? count($model->andWhere(['>=', (date('Y') - date('Y', strtotime('yob' . "-" . 'mob' . "-" . 'dob'))), 7])->andWhere(['<=', (date('Y') - date('Y', strtotime('yob' . "-" . 'mob' . "-" . 'dob'))), 10])->all()) * 100 / $total : 0,
            '11-12' => $total > 0 ? count($model->andWhere(['>=', (date('Y') - date('Y', strtotime('yob' . "-" . 'mob' . "-" . 'dob'))), 11])->andWhere(['<=', (date('Y') - date('Y', strtotime('yob' . "-" . 'mob' . "-" . 'dob'))), 12])->all()) * 100 / $total : 0,
            '13-15' => $total > 0 ? count($model->andWhere(['>=', (date('Y') - date('Y', strtotime('yob' . "-" . 'mob' . "-" . 'dob'))), 13])->andWhere(['<=', (date('Y') - date('Y', strtotime('yob' . "-" . 'mob' . "-" . 'dob'))), 15])->all()) * 100 / $total : 0,
            '16+' => $total > 0 ? count($model->andWhere(['>=', (date('Y') - date('Y', strtotime('yob' . "-" . 'mob' . "-" . 'dob'))), 16])->all()) * 100 / $total : 0,
        ];

        return [
            'gender' => $gender,
            'age' => $age,
        ];
    }

}
