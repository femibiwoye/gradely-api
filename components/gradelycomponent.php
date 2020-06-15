<?php

namespace app\components;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
class GradelyComponent extends Component
{
  //this percentageScore property can be changed
  private $percentageScore = 25;
  private $percentageScoreForNextQuestion = 30;
  private $numberQuestionsPerTime = 30;

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

      $convertPercentageScore = $this->percentageScore();

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


  public function getPercentageForNextQuestionUpdate($totalFailed,$currentDifficulty){

    $convertPercentageScore = $this->percentageScore();

    $requiredPercentageScore = $convertPercentageScore;

    $queryLimit=[];
    /*if total number of failed questions is less than percentage that 
    means student can proceed to the next next difficulty but still within 
    the next question
    */

    //under easy check if student is fit toproceed to the next difficulty
    //next difficulty will allow us know the next difficulty to proceed to
    //proceed being true or false will allow us know wether the student has already been taking the current set of difficulty
    //if for instanc ei have been taking easy and due to my performance i am also being requested to take easy, in order not for me 
    //the same easy questions i had already taken to get presented to me proceed will have to be false which means i will still 
    //be seeing questions within the current difficulty
    if($currentDifficulty == 'easy' && $totalFailed < $requiredPercentageScore)
      return ['nextDificulty' => 'medium', 'proceed' => true, 'nextTopic' => false];

    elseif($currentDifficulty == 'easy' && $totalFailed > $requiredPercentageScore)
      return ['nextDificulty' => 'easy', 'proceed' => false, 'nextTopic' => false];
    
    if($currentDifficulty == 'medium' && $totalFailed < $requiredPercentageScore)
      return ['nextDificulty' => 'hard', 'proceed' => true, 'nextTopic' => false];

    elseif($currentDifficulty == 'medium' && $totalFailed > $requiredPercentageScore)
      return ['nextDificulty' => 'medium', 'proceed' => false, 'nextTopic' => false];

    if($currentDifficulty == 'hard' && $totalFailed < $requiredPercentageScore)
      return ['nextDificulty' => 'easy', 'proceed' => true, 'nextTopic' => true];

    elseif($currentDifficulty == 'hard' && $totalFailed > $requiredPercentageScore)
      return ['nextDificulty' => 'hard', 'proceed' => false, 'nextTopic' => false];

    //$queryLimit= ['easy' => 2,'medium' => 3,'hard' => 2];
    //return ($this->percentageScore*30) / 100 ;
}

  public function moveToNextTopic(){

    
  }

  private function percentageScore(){
    return ($this->percentageScore*30) / 100;
  }
}
