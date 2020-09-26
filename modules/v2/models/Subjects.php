<?php

namespace app\modules\v2\models;

use app\modules\v2\components\Utility;
use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "subjects".
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property string|null $category
 * @property string|null $image
 * @property int $status
 * @property int|null $school_id
 * @property int $approved
 * @property int|null $approved_by
 * @property int|null $diagnostics
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property ClassSubjects[] $classSubjects
 * @property SchoolSubject[] $schoolSubjects
 * @property TeacherClassSubjects[] $teacherClassSubjects
 */
class Subjects extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'subjects';
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

    public function fields()
    {
        $fields = parent::fields();
        $fields['image'] = function ($model) {
            return Utility::AbsoluteImage($model->image,'subjects');
        };
        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['slug', 'name', 'description'], 'required'],
            [['description', 'category', 'image'], 'string'],
            [['status', 'school_id', 'approved', 'approved_by', 'diagnostics'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['slug', 'name'], 'string', 'max' => 100],
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
            'description' => 'Description',
            'category' => 'Category',
            'status' => 'Status',
            'school_id' => 'School ID',
            'approved' => 'Approved',
            'approved_by' => 'Approved By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[ClassSubjects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClassSubjects()
    {
        return $this->hasMany(ClassSubjects::className(), ['subject_id' => 'id']);
    }

    /**
     * Gets query for [[SchoolSubjects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSchoolSubjects()
    {
        return $this->hasMany(SchoolSubject::className(), ['subject_id' => 'id']);
    }

    /**
     * Gets query for [[TeacherClassSubjects]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeacherClassSubjects()
    {
        return $this->hasMany(TeacherClassSubjects::className(), ['subject_id' => 'id']);
    }

    public function getImageUrl()
    {
        if (empty($this->image))
            $image = null;
        elseif (strpos($this->image, 'http') !== false)
            $image = $this->image;
        else {
            $image = Yii::$app->params['baseURl'] . '/images/users/' . $this->image;
        }
        return $image;
    }
}
