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
            $currDate = date('m-d');

            switch (true) {
                case date("m-d", strtotime(Yii::$app->params['first_term_start'])) <= $currDate && date("m-d", strtotime(Yii::$app->params['first_term_end'])) >= $currDate:
                    if($this->weekOnly){
                        $term =  SessionWeek::widget(['start' => Yii::$app->params['first_term_start'], 'end' => Yii::$app->params['first_term_end']]);
                        break;
                    }
                    $term = 'First';
                    break;

//                case date("m-d", strtotime(Yii::$app->params['first_term_end'])) <= $currDate && '12-31' >= $currDate:
//                    if($this->weekOnly){
//                        $term =  SessionWeek::widget(['start' => Yii::$app->params['second_term_start'], 'end' => Yii::$app->params['second_term_end']]);
//                        break;
//                    }
//                    $term = 'Second';
//                    break;

                case date("m-d", strtotime(Yii::$app->params['second_term_start'])) <= $currDate && date("m-d", strtotime(Yii::$app->params['second_term_end'])) >= $currDate:
                    if($this->weekOnly){
                        $term =  SessionWeek::widget(['start' => Yii::$app->params['second_term_start'], 'end' => Yii::$app->params['second_term_end']]);
                        break;
                    }
                    $term = 'Second';
                    break;

                case date("m-d", strtotime(Yii::$app->params['second_term_end'])) <= $currDate && date("m-d", strtotime(Yii::$app->params['third_term_start'])) >= $currDate:
                    if($this->weekOnly){
                        $term =  SessionWeek::widget(['start' => Yii::$app->params['third_term_start'], 'end' => Yii::$app->params['third_term_end']]);
                        break;
                    }
                    $term = 'Third';
                    break;

                case date("m-d", strtotime(Yii::$app->params['third_term_start'])) < $currDate && date("m-d", strtotime(Yii::$app->params['third_term_end'])) > $currDate :
                    if($this->weekOnly){
                        $term =  SessionWeek::widget(['start' => Yii::$app->params['third_term_start'], 'end' => Yii::$app->params['third_term_end']]);
                        break;
                    }
                    $term = 'Third';
                    break;
            }
            return $this->id = $term;
        }
        $model = SchoolCalendar::findOne(['school_id' => $this->id]);
        $term = 'First';
        $currDate = date('m-d');
        if ($model) {
            switch ($model) {
                case date("m-d", strtotime($model->first_term_start)) <= $currDate && date("m-d", strtotime($model->first_term_end)) >= $currDate:
                    $term = 'First';
                    break;

                case date("m-d", strtotime($model->first_term_end)) <= $currDate && '12-31' >= $currDate:
                    $term = 'Second';
                    break;

                case date("m-d", strtotime($model->second_term_start)) <= $currDate && date("m-d", strtotime($model->second_term_end)) >= $currDate:
                    $term = 'Second';
                    break;

                case date("m-d", strtotime($model->second_term_end)) <= $currDate && date("m-d", strtotime($model->third_term_start)) >= $currDate:
                    $term = 'Third';
                    break;

                case date("m-d", strtotime($model->third_term_start)) < $currDate && date("m-d", strtotime($model->third_term_end)) > $currDate :
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