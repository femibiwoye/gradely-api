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
 * @property int $general Default selected curriculum on signup
 * @property string|null $country
 * @property int|null $school_id
 * @property int $approved If approved is one, it means it should be available for every school on the platform
 * @property string $created_at
 *
 * @property SchoolCurriculum[] $schoolCurriculums
 */
class ExamType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['slug', 'name', 'title', 'description'], 'required'],
            [['description'], 'string'],
            [['general', 'school_id', 'approved'], 'integer'],
            [['created_at'], 'safe'],
            [['slug', 'name'], 'string', 'max' => 200],
            [['title', 'country'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'slug' => 'Slug',
            'name' => 'Name',
            'title' => 'Title',
            'description' => 'Description',
            'general' => 'General',
            'country' => 'Country',
            'school_id' => 'School ID',
            'approved' => 'Approved',
            'created_at' => 'Created At',
        ];
    }





    /**
     * Gets query for [[SchoolCurriculums]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolCurriculums()
    {
        return $this->hasMany(SchoolCurriculum::className(), ['curriculum_id' => 'id']);
    }
}
