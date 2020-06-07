<?php

namespace app\components;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
class GradelyComponent extends Component
{
  public function getTopHomeworkPercentage($topHomeworkPercentageTotal_questions,$topHomeworkPercentageCorrect)
  {
    return $topHomeworkPercentageTotal_questions/ $topHomeworkPercentageCorrect *100;

  }

  public function getHomeworkAdaptivityCalculation($checkStudentHomeworkActivities){

    $storeAll = [];
    foreach($checkStudentHomeworkActivities as $checkStudentHomeworkActivity){
      $storeAll[] = $checkStudentHomeworkActivity->answer;
    }
    
    return $storeAll;

  }
}
