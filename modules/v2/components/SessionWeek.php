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
        $this->start = date("m-d", strtotime($this->start));
        $this->end = date("m-d");

        if ($this->start > $this->end) return datediffInWeeks($this->end, $this->start);

        //date_default_timezone_set("Africa/Lagos");
        $first = \DateTime::createFromFormat('m-d', $this->start);
        $second = \DateTime::createFromFormat('m-d', $this->end);
        $this->start = floor($first->diff($second)->days / 7) + 1;
        return $this->start;

    }

    public function run()
    {
        return Html::decode($this->start, $this->end);
    }
}