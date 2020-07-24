<?php

namespace app\modules\v2\models;

use Yii;
use yii\behaviors\SluggableBehavior;

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
            [['contact_role'], 'string', 'max' => 50],
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

    public function getSchoolOptions()
    {
        return $this->hasMany(SchoolOptions::className(), ['school_id' => 'id']);
    }

    public function getClasses()
    {
        return $this->hasMany(Classes::className(), ['school_id' => 'id']);
    }
}
