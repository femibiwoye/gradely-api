<?php

namespace app\modules\v2\models;

use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "schools".
 *
 * @property int $id
 * @property int $user_id
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
 * @property string|null $naming_format
 * @property string $created_at
 *
 * @property SchoolOptions[] $schoolOptions
 */
class Schools extends \yii\db\ActiveRecord
{

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
            [['contact_role'], 'string', 'max' => 50],
            [['phone', 'phone2'], 'string', 'max' => 15],

            [['name', 'abbr'], 'required', 'on' => 'school_signup'],
        ];
    }

    /**
     * {@inheritdoc}
     */
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
     * Gets query for [[SchoolOptions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolOptions()
    {
        return $this->hasMany(SchoolOptions::className(), ['school_id' => 'id']);
    }

    public function getClasses()
    {
        return $this->hasMany(Classes::className(), ['school_id' => 'id']);
    }
}
