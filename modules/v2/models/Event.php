<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "event".
 *
 * @property int $id
 * @property string $title This is the title of the event
 * @property string|null $sub_title Incase there is a shorter description
 * @property string|null $description
 * @property string|null $link This is for external link of event
 * @property string|null $images This is json data type and it accept multiple image link
 * @property string|null $videos This is json data type and it accept multiple video link
 * @property string|null $location Maybe online or physical address
 * @property string|null $organiser Name of the organiser
 * @property string|null $start_date When the event starts
 * @property string|null $end_date When the event ends
 * @property int $created_by
 * @property int|null $updated_by
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Event extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'event';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'created_by'], 'required'],
            [['description', 'link'], 'string'],
            [['images', 'videos', 'start_date', 'end_date', 'created_at', 'updated_at'], 'safe'],
            [['created_by', 'updated_by'], 'integer'],
            [['title'], 'string', 'max' => 100],
            [['sub_title', 'location', 'organiser'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'sub_title' => 'Sub Title',
            'description' => 'Description',
            'link' => 'Link',
            'images' => 'Images',
            'videos' => 'Videos',
            'location' => 'Location',
            'organiser' => 'Organiser',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
