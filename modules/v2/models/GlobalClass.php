<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "global_class".
 *
 * @property int $id
 * @property string $class_id
 * @property int $status
 * @property string|null $description
 *
 * @property Feed[] $feeds
 */
class GlobalClass extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'global_class';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['class_id'], 'required'],
            [['status'], 'integer'],
            [['class_id'], 'string', 'max' => 20],
            [['description'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'class_id' => 'Class ID',
            'status' => 'Status',
            'description' => 'Description',
        ];
    }

    /**
     * Gets query for [[Feeds]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFeeds()
    {
        return $this->hasMany(Feed::className(), ['global_class' => 'id']);
    }

    public function getSchoolClasses($id)
    {
        return Classes::find()
            ->select([
                'classes.id',
                'classes.slug',
                'class_code',
                'class_name',
                'abbreviation',
                'global_class_id',
                'school_id',
                'schools.name school_name'
            ])
            ->leftJoin('schools', 'schools.id = classes.school_id')
            ->where(['school_id' => $id,'global_class_id'=>$this->id])
            ->asArray()
            ->all();
    }
}
