<?php

namespace app\modules\v2\components;

use app\modules\v2\models\SchoolCalendar;
use yii\base\Widget;
use yii\helpers\Html;
use Yii;

class SessionTermOnly extends Widget
{
    public $id;
    public $nonSchool = false;
    public $weekOnly = false;

    public function init()
    {
        parent::init();
        if ($this->nonSchool) {
            $term = 'First';
            $currDate = date('Y-m-d');

            switch (true) {
                case Yii::$app->params['first_term_start'] <= $currDate && Yii::$app->params['first_term_end'] >= $currDate:
                    if ($this->weekOnly) {
                        $term = SessionWeek::widget(['start' => Yii::$app->params['first_term_start'], 'end' => Yii::$app->params['first_term_end']]);
                        break;
                    }
                    $term = 'First';
                    break;


                case Yii::$app->params['second_term_start'] <= $currDate && Yii::$app->params['second_term_end'] >= $currDate:
                    if ($this->weekOnly) {
                        $term = SessionWeek::widget(['start' => Yii::$app->params['second_term_start'], 'end' => Yii::$app->params['second_term_end']]);
                        break;
                    }
                    $term = 'Second';
                    break;


                case Yii::$app->params['third_term_start'] < $currDate && Yii::$app->params['third_term_end'] > $currDate :
                    if ($this->weekOnly) {
                        $term = SessionWeek::widget(['start' => Yii::$app->params['third_term_start'], 'end' => Yii::$app->params['third_term_end']]);
                        break;
                    }
                    $term = 'Third';
                    break;
            }
            return $this->id = $term;
        }
        $model = SchoolCalendar::findOne(['school_id' => $this->id]);
        $term = 'First';
        $currDate = date('Y-m-d');
        if ($model) {

            switch ($model) {
                case $model->first_term_start <= $currDate && $model->first_term_end >= $currDate:
                    if ($this->weekOnly) {
                        $term = SessionWeek::widget(['start' => $model->first_term_start, 'end' => $model->first_term_end]);
                        break;
                    }
                    $term = 'First';
                    break;


                case $model->second_term_start <= $currDate && $model->second_term_end >= $currDate:
                    if ($this->weekOnly) {
                        $term = SessionWeek::widget(['start' => $model->second_term_start, 'end' => $model->second_term_end]);
                        break;
                    }
                    $term = 'Second';
                    break;

                case $model->third_term_start < $currDate && $model->third_term_end > $currDate :
                    if ($this->weekOnly) {
                        $term = SessionWeek::widget(['start' => Yii::$app->params['third_term_start'], 'end' => Yii::$app->params['third_term_end']]);
                        break;
                    }
                    $term = 'Third';
                    break;
            }
        }
        return $this->id = $term;
    }

    public function run()
    {
        return Html::decode($this->id);
    }
}