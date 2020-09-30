<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "website_error".
 *
 * @property int $id
 * @property string $error
 * @property string|null $user
 * @property string|null $current
 * @property string|null $previous
 * @property string|null $source This is to tell where the error is coming from. Maybe from API, Frontend Web, Mobile app, etc.
 * @property int $status
 * @property int $emailed This is to determine if the email has been sent to error manager.
 * @property string|null $raw This is the error object
 * @property string $created_at
 */
class WebsiteError extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'website_error';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['error'], 'required'],
            [['current', 'previous', 'raw'], 'string'],
            [['status', 'emailed'], 'integer'],
            [['created_at'], 'safe'],
            [['error'], 'string', 'max' => 100],
            [['user'], 'string', 'max' => 255],
            [['source'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'error' => 'Error',
            'user' => 'User',
            'current' => 'Current',
            'previous' => 'Previous',
            'source' => 'Source',
            'status' => 'Status',
            'emailed' => 'Emailed',
            'raw' => 'Raw',
            'created_at' => 'Created At',
        ];
    }
}
