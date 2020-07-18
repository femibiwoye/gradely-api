<?php

namespace app\modules\v2\models;

use yii\base\Widget;
use yii\helpers\Html;

class GenerateString extends Widget
{
    public $length = 30;
    public $type; //Types include, char, numbers, alphanumeric;

    public function init()
    {
        parent::init();
        if ($this->type == 'char')
            $type = "abcdefghijklmnopqrstuvwxyz";
        elseif ($this->type == 'number')
            $type = "0123456789";
        else
            $type = "0123456789abcdefghijklmnopqrstuvwxyz";

        $this->length = substr(str_shuffle(str_repeat($type, $this->length)), 0, $this->length);

        return $this->length;
    }

    public function run()
    {
        return Html::decode($this->length, $this->type);
    }
}