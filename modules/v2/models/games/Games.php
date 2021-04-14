<?php

namespace app\modules\v2\models\games;

use Yii;
use yii\behaviors\SluggableBehavior;

/**
 * This is the model class for table "games".
 *
 * @property int $id
 * @property string $category_name
 * @property string $group
 * @property string $level
 * @property string $subject
 * @property string $topic
 * @property string $game_id
 * @property string $game_title
 * @property string $description
 * @property string $image
 * @property string $token
 * @property string|null $provider
 * @property int|null $status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Games extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'games';
    }

    public function behaviors()
    {
        return [
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'game_title',
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
            [['category_name', 'group', 'level', 'subject', 'topic', 'game_id', 'game_title', 'description', 'image', 'token'], 'required'],
            [['description', 'image'], 'string'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['category_name', 'group', 'level', 'subject', 'topic', 'game_id', 'game_title', 'provider', 'slug'], 'string', 'max' => 100],
            [['token'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_name' => 'Category Name',
            'group' => 'Group',
            'level' => 'Level',
            'subject' => 'Subject',
            'topic' => 'Topic',
            'game_id' => 'Game ID',
            'game_title' => 'Game Title',
            'description' => 'Description',
            'image' => 'Image',
            'token' => 'Token',
            'provider' => 'Provider',
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
