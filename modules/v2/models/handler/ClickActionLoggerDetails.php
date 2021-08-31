<?php

namespace app\modules\v2\models\handler;

use Yii;

/**
 * This is the model class for table "click_action_logger_details".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action_name This is unique name give to he monitoring process. e.g, monitory certain buttons clicked during back2school21 campaign
 * @property string $page_name e.g during back2School campaign, we want to monitor claim offer buttons clicked. page sample include feed_body, feed_top, catchup_top, report_top
 * @property string|null $url
 * @property int|null $click_count
 * @property string $created_at
 */
class ClickActionLoggerDetails extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'click_action_logger_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'click_count'], 'integer'],
            [['action_name', 'page_name'], 'required'],
            [['url'], 'string'],
            [['created_at'], 'safe'],
            [['action_name', 'page_name'], 'string', 'max' => 50],
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
            'action_name' => 'Action Name',
            'page_name' => 'Page Name',
            'url' => 'Url',
            'click_count' => 'Click Count',
            'created_at' => 'Created At',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->handler;
    }
}
