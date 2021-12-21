<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "remarks".
 *
 * @property int $id
 * @property string $type
 * @property int $creator_id The user giving remark
 * @property int $receiver_id The receiver could either be student, homework, etc
 * @property int $subject_id remark subject_id
 * @property string $remark
 * @property string $created_at
 */
class Remarks extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'remarks';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'creator_id', 'receiver_id', 'remark','subject_id'], 'required'],
            [['type', 'remark'], 'string'],
            [['creator_id', 'receiver_id'], 'integer'],
            [['created_at'], 'safe'],
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['creator'] = 'creatorProfile';

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'creator_id' => 'Creator ID',
            'receiver_id' => 'Receiver ID',
            'remark' => 'Remark',
            'created_at' => 'Created At',
        ];
    }

    public function getCreatorProfile()
    {
        return $this->hasOne(User::className(),['id'=>'creator_id']);
    }
}
