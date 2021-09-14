<?php

namespace app\modules\v2\models\handler;

use Yii;

/**
 * This is the model class for table "request_logger".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $method
 * @property string $url
 * @property string|null $code
 * @property string|null $request
 * @property string|null $response
 * @property string|null $created_at
 */
class RequestLogger extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request_logger';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['method', 'url'], 'required'],
            [['url'], 'string'],
            [['request', 'response', 'created_at'], 'safe'],
            [['method', 'code'], 'string', 'max' => 50],
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
            'method' => 'Method',
            'url' => 'Url',
            'code' => 'Code',
            'request' => 'Request',
            'response' => 'Response',
            'created_at' => 'Created At',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->handler;
    }
}
