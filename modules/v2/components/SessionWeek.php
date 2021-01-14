<?php

namespace app\modules\v2\components;

use yii\base\Widget;
use yii\helpers\Html;

class SessionWeek extends Widget
{
    public $start;
    public $end;

    public function init()
    {
        parent::init();
        $this->start = date("Y-m-d", strtotime($this->start));
        $this->end = date("Y-m-d");

        //if ($this->start > $this->end) return datediffInWeeks($this->end, $this->start);

        //date_default_timezone_set("Africa/Lagos");
        $first = \DateTime::createFromFormat('Y-m-d', $this->start);
        $second = \DateTime::createFromFormat('Y-m-d', $this->end);
        $this->start = floor($first->diff($second)->days / 7) + 1;
        return $this->start;

    }

    public function run()
    {
        return Html::decode($this->start, $this->end);
    }
}