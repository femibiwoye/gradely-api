<?php

namespace app\modules\v2\models\games;

use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "level".
 *
 * @property int $id
 * @property string|null $slug
 * @property string|null $name
 * @property string|null $description
 * @property int|null $status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Level extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'level';
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
            [['description'], 'string'],
            [['status'], 'integer'],
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
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->get('game');
    }
}
