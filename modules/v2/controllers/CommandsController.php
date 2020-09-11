<?php

namespace app\modules\v2\controllers;


use app\modules\v2\models\SchoolCalendar;
use Yii;
use yii\rest\Controller;



/**
 * Auth controller
 */
class CommandsController extends Controller
{
    public function actionUpdateSchoolCalendar()
    {
         return SchoolCalendar::updateAll([
            'first_term_start'=>Yii::$app->params['first_term_start'],
            'first_term_end'=>Yii::$app->params['first_term_end'],
            'second_term_start'=>Yii::$app->params['second_term_start'],
            'second_term_end'=>Yii::$app->params['second_term_end'],
            'third_term_start'=>Yii::$app->params['third_term_start'],
            'third_term_end'=>Yii::$app->params['third_term_end'],
            'year'=>date('Y'),
            'session_name'=>date('Y')+1
        ],['status'=>1]);
    }

}
