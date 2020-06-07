<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "school_calendar".
 *
 * @property int $id
 * @property int $school_id
 * @property string $session_name
 * @property int $year Year of the school session for the calendar
 * @property string $first_term_start
 * @property string $first_term_end
 * @property string $second_term_start
 * @property string $second_term_end
 * @property string $third_term_start
 * @property string $third_term_end
 * @property int $status
 * @property string $created_at
 */
class SchoolCalendar extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'school_calendar';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['school_id', 'session_name', 'year', 'first_term_start', 'first_term_end', 'second_term_start', 'second_term_end', 'third_term_start', 'third_term_end'], 'required'],
            [['school_id', 'year', 'status'], 'integer'],
            [['first_term_start', 'first_term_end', 'second_term_start', 'second_term_end', 'third_term_start', 'third_term_end', 'created_at'], 'safe'],
            [['session_name'], 'string', 'max' => 100],
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
            'session_name' => 'Session Name',
            'year' => 'Year',
            'first_term_start' => 'First Term Start',
            'first_term_end' => 'First Term End',
            'second_term_start' => 'Second Term Start',
            'second_term_end' => 'Second Term End',
            'third_term_start' => 'Third Term Start',
            'third_term_end' => 'Third Term End',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }
}
