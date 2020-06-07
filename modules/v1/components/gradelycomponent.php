<?php

namespace app\modules\v1\components;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
class GradelyComponent extends Component
{
  public function getTopHomeworkPercentage($topHomeworkPercentageTotal_questions,$topHomeworkPercentageCorrect)
  {
    return $topHomeworkPercentageTotal_questions/ $topHomeworkPercentageCorrect *100;

  }
}
