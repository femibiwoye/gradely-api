<?php

namespace app\modules\v2\components;

use yii\base\Model;
use Yii;

class GetSpecificData extends Model
{

    public function term($currentDate)
    {
        $currDate = date('m-d', strtotime($currentDate));
        $y = date('Y', strtotime($currentDate));
        $term = null;
        if(date('m-d', strtotime(Yii::$app->params['first_term_start'])) <= $currDate && date('m-d', strtotime(Yii::$app->params['first_term_end'])) >= $currDate) {
            $term = 'First';
        }

        if(date('m-d', strtotime(Yii::$app->params['second_term_start'])) <= $currDate && date('m-d', strtotime(Yii::$app->params['second_term_end'])) >= $currDate) {
            $term = 'Second';
        }


        if(date('m-d', strtotime(Yii::$app->params['third_term_start'])) < $currDate && date('m-d', strtotime(Yii::$app->params['third_term_end'])) > $currDate) {
            $term = 'Third';
        }

        if(date('Y-m-d', strtotime(Yii::$app->params['third_term_start'])) < $currDate && date('m-d', strtotime(Yii::$app->params['third_term_end'])) > $currDate) {
            $term = 'Third';
        }

        if($y.'-'.date('m-d', strtotime(Yii::$app->params['first_term_start'])) <= $currentDate && ($y+1).'-'.date('m-d', strtotime(Yii::$app->params['first_term_end'])) >= $currentDate) {
            $term = 'First';
        }
        return $term;
    }

    public function week($start, $end)
    {
        parent::init();
        $start = date("Y-m-d", strtotime($start));


        $first = \DateTime::createFromFormat('Y-m-d', $start);
        $second = \DateTime::createFromFormat('Y-m-d', $end);
        return floor($first->diff($second)->days / 7) + 1;
    }
}