<?php

namespace app\modules\v2\sms\models;

use Yii;

/**
 * This is the model class for table "schools".
 *
 * @property int $id
 * @property int $school_id
 * @property int|null $status
 * @property string $school_key
 * @property string $school_secret
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property int|null $approved
 * @property string|null $approved_by
 * @property string $approved_at
 */
class Schools extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'schools';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['school_id', 'school_key', 'school_secret'], 'required'],
            [['school_id', 'status', 'approved'], 'integer'],
            [['created_at', 'updated_at', 'approved_at'], 'safe'],
            [['school_key', 'school_secret', 'updated_by', 'approved_by'], 'string', 'max' => 45],
            [['school_id'], 'unique'],
            [['school_key'], 'unique'],
            [['school_secret'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'school_id' => 'School ID',
            'status' => 'Status',
            'school_key' => 'School Key',
            'school_secret' => 'School Secret',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
            'approved' => 'Approved',
            'approved_by' => 'Approved By',
            'approved_at' => 'Approved At',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->sms;
    }
}
