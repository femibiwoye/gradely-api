<?php

namespace app\modules\v2\models\games;

use Yii;

/**
 * This is the model class for table "game_like".
 *
 * @property int $id
 * @property int $user_id
 * @property int $game_id
 * @property int $status
 * @property string|null $created_at
 */
class GameLike extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'game_like';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'game_id', 'status'], 'required'],
            [['user_id', 'game_id', 'status'], 'integer'],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'game_id' => 'Game ID',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->get('game');
    }
}
