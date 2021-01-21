<?php

namespace app\modules\v2\models;

use Yii;
use yii\behaviors\SluggableBehavior;


/**
 * This is the model class for table "exam_type".
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $title
 * @property string $description
 * @property int $general General is 1 if the curriculum should be checked on school setup
 * @property string|null $country
 * @property int|null $school_id
 * @property int $approved If approved is one, it means it should be available for every school on the platform
 * @property int|null $approved_by ID of the admin that approved it
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property SchoolCurriculum[] $schoolCurriculums
 */

class ExamType extends \yii\db\ActiveRecord
{
	public static function tableName()
	{
		return 'exam_type';
	}

    public function behaviors()
    {
        return [
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'name',
                'ensureUnique' => true
            ],
        ];
    }

	public function rules()
	{
		return [
			[['slug', 'name', 'title', 'description'], 'required'],
			[['description'], 'string'],
			[['general'], 'integer'],
			[['created_at'], 'safe'],
			[['slug', 'name'], 'string', 'max' => 200],
			[['title'], 'string', 'max' => 100],
		];
	}

	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'slug' => 'Slug',
			'name' => 'Name',
			'title' => 'Title',
			'description' => 'Description',
			'general' => 'General',
			'created_at' => 'Created At',
		];
	}

	public function getSchoolCurriculums()
	{
		return $this->hasMany(SchoolCurriculum::className(), ['curriculum_id' => 'id']);
	}
}
