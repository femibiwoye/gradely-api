<?php

namespace app\controllers;


use Yii;
use yii\web\Controller;


/**
 * Auth controller
 */
class TestController extends Controller
{

    public function actionServerTimezone()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        $this->layout = 'empty';
        return $this->render('/site/about');
    }

    public function actionServerDatabase()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        //return $this->render('/site/db');
        $this->layout = 'empty';
        $result =  Yii::$app->db->createCommand('SHOW VARIABLES LIKE \'%time_zone%\';
SELECT NOW();')->queryAll();
        print_r($result);
    }
}

