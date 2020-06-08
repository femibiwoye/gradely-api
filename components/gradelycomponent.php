<?php

namespace app\components;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
class GradelyComponent extends Component
{
  //this percentageScore property can be changed
  private $percentageScore = 25;

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

  public function getPercentageForNextQuestion($totalFailedEasy, $totalFailedMedium, $totalFailedHard){
      //check if total failed of last performance is greater than 25%
      // $totalFailedEasy = 4; 
      // $totalFailedMedium = 3; 
      // $totalFailedHard = 2;
      $totalFailed = $totalFailedEasy + $totalFailedMedium + $totalFailedHard;
      $convertPercentageScore = ($this->percentageScore*30) / 100;
      $splitToThree = $convertPercentageScore / 3;
      //var_dump($splitToThree); exit;
      /*
      check if total failed is greater than percentage score, default percentage is 25 percent
      but it can alwaysbe changed, if student failed above 25% of the questions, then the next set of
      questins 25% should be added to the original set of questions.
      */

      $queryLimit=[];
      if($totalFailed > $convertPercentageScore){

        //get worse performance
        $getWorsePerformance = max([$totalFailedEasy,$totalFailedMedium,$totalFailedHard]);

        //if the student failed more of hard show more hard questions in the next 
        if($getWorsePerformance == $totalFailedHard){
          $queryLimit[] = ['easy' => 2,'medium' => 2,'hard' => 3];
          return $queryLimit;
        }

        //if the student failed more of medium show more medium questions in the next 
        elseif($getWorsePerformance == $totalFailedMedium){
          $queryLimit[] = ['easy' => 2,'medium' => 3,'hard' => 2];
          return $queryLimit;
        }

        //if the student failed more of easy show more easy questions in the next 
        elseif($getWorsePerformance == $totalFailedEasy){
          //$queryLimit = "easy=3,medium=2,hard=2";
          $queryLimit[] = ['easy' => 3,'medium' => 2,'hard' => 2];
          return $queryLimit;
        }

        //return $queryLimit;
        //return max([$totalFailedEasy,$totalFailedMedium,$totalFailedHard]);
      }

        //$queryLimit= ['easy' => 2,'medium' => 3,'hard' => 2];
        return ($this->percentageScore*30) / 100 ;
  }

  public function moveToNextTopic(){

    
  }
}
