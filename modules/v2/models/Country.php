<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "country".
 *
 * @property int $id
 * @property string $sortname
 * @property string $name
 * @property string|null $slug
 */
class Country extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'country';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sortname', 'name'], 'required'],
            [['sortname'], 'string', 'max' => 3],
            [['name', 'slug'], 'string', 'max' => 150],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sortname' => 'Sortname',
            'name' => 'Name',
            'slug' => 'Slug',
        ];
    }
}
