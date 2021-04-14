<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "user_type_app_permission".
 *
 * @property int $id
 * @property string|null $user_type
 * @property string|null $app_name
 * @property int|null $status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class UserTypeAppPermission extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_type_app_permission';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['user_type'], 'string', 'max' => 20],
            [['app_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_type' => 'User Type',
            'app_name' => 'App Name',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
