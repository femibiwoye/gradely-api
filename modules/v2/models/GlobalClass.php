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
}
