<?php

namespace app\modules\v2\models;

use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "classes".
 *
 * @property int $id
 * @property int $school_id
 * @property int $global_class_id
 * @property string $slug
 * @property string $class_name e.g Senior secondary School 1
 * @property string $abbreviation
 * @property string $class_code e.g HBY/SSS1
 * @property string $created_at
 */
class Classes extends \yii\db\ActiveRecord
{
	const SCENERIO_CREATE_CLASS = 'create_class';
	const SCENERIO_UPDATE_CLASS = 'update_class';
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'classes';
	}

    /**
     * Get and ensure unique name for class slug and class abbreviation
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'class_name',
                //'ensureUnique' => true
            ],
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'abbreviation',
                //'ensureUnique' => true,
                'slugAttribute' => 'abbreviation'
            ]

        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			//[['school_id', 'global_class_id', 'slug', 'class_name', 'abbreviation', 'class_code'], 'required'],
			[['school_id', 'global_class_id'], 'integer'],
			[['created_at'], 'safe'],
			[['slug', 'class_name'], 'string', 'max' => 255],
			[['abbreviation', 'class_code'], 'string', 'max' => 20],
			[['class_code'], 'unique'],
			[['global_class_id','class_name','class_code'], 'required',
				 'on' => self::SCENERIO_CREATE_CLASS
			],
			[['global_class_id','class_name','abbreviation'], 'required',
				 'on' => self::SCENERIO_UPDATE_CLASS
			]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'school_id' => 'School ID',
			'global_class_id' => 'Global Class ID',
			'slug' => 'Slug',
			'class_name' => 'Class Name',
			'abbreviation' => 'Abbreviation',
			'class_code' => 'Class Code',
			'created_at' => 'Created At',
		];
	}

	/*public function fields() {
		return [
			'id',
			'school_id',
			'global_class_id',
			'slug',
			'class_name',
			'abbreviation',
			'class_code',
			'created_at'
		];
	}*/

	public function getSchool() {
		return $this->hasOne(Schools::className(), ['id' => 'school_id']);
	}

    public function getGlobalClass() {
        return $this->hasOne(GlobalClass::className(), ['id' => 'global_class_id']);
    }


    public function getStudentSchool()
    {
        return $this->hasMany(StudentSchool::className(), ['class_id' => 'id']);
    }

    public function getHomeworks() {
    	return $this->hasMany(Homeworks::className(), ['class_id' => 'id']);
    }
}
