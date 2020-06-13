<?php

namespace app\modules\v1\school\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\{Controller,Response};
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\modules\v1\utility\Utility; 
use app\models\{Schools,Classes,GlobalClass,StudentSchool,User,Parents,SchoolCalendar,Homeworks,HomeworkQuestions,TutorSession,UserProfile,SchoolCurriculum,SchoolClassCurriculum};
/**
 * Schools controller
 */
class SummariesController extends ActiveController
{
    public $modelClass = 'api\models\User';

    private $request; 

    public function beforeAction($action)
    {
        $this->request = \yii::$app->request->post();
        return parent::beforeAction($action);
    }
    
    /**
     * {@inheritdoc}
     */

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'index' => ['get'],
                ],
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['index']
            ],            
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex(){
        
        $startRange = ""; $endRange = "";
        if(isset($this->request['startRange']) && isset($this->request['endRange'])){
            $startRange = $this->request['startRange'];  $endRange = $this->request['endRange']; 
        }
        $allHomeWorkCount = count(Homeworks::find()->where(['school_id' => Utility::getSchoolUserId()])->all());
        $pastHomework = count(Homeworks::find()
                ->where([
                    'and',
                    ['school_id' => Utility::getSchoolUserId()],
                    //['<', ['close_date' => date('Y-m-d H:i:s')]]
                    ['<', 'close_date', date('Y-m-d H:i:s')]
                ])->all());
        $activeHomeWork = count(Homeworks::find()->where(['school_id' => Utility::getSchoolUserId(),'access_status' => 1])->all());
        $yetToStartHomeWork = count(Homeworks::find()->where(['school_id' => Utility::getSchoolUserId(),'status' => 1])->all());
        $homeworkRange = count(Homeworks::find()
                ->where([
                    'and',
                    ['school_id' => Utility::getSchoolUserId()],
                    //['>=', ['open_date' => date('Y-m-d H:i:s')]],
                    ['>','open_date',date('Y-m-d H:i:s')],
                    //['=<', ['close_date' => date('Y-m-d H:i:s')]]
                    ['<','close_date',date('Y-m-d H:i:s')]
                ])->all());
            $liveClassSessions = count(Classes::find()
                                    ->select('classes.*')
                                    ->leftJoin('tutor_session', '`classes`.`id` = `tutor_session`.`class`')
                                    //->where(['classes.school' => 3])
                                    ->where(['classes.school_id' => Utility::getSchoolUserId()])
                                    ->where(['tutor_session.is_school' => 1])
                                    ->where(['tutor_session.status' => 2])
                                    ->all()
                                );
            $pendingSessions = count(Classes::find()
                                    ->select('classes.*')
                                    ->leftJoin('tutor_session', '`classes`.`id` = `tutor_session`.`class`')
                                    //->where(['classes.school' => 3])
                                    ->where(['classes.school_id' => Utility::getSchoolUserId()])
                                    ->where(['tutor_session.is_school' => 1])
                                    ->where(['tutor_session.status' => 1])
                                    ->all()
                                );
            $ongoingSessions = count(Classes::find()
                                    ->select('classes.*')
                                    ->leftJoin('tutor_session', '`classes`.`id` = `tutor_session`.`class`')
                                    //->where(['classes.school' => 3])
                                    ->where(['classes.school_id' => Utility::getSchoolUserId()])
                                    ->where(['tutor_session.is_school' => 1])
                                    ->where(['tutor_session.status' => 2])
                                    ->all()
                                );
            $completedSessions = count(Classes::find()
                                    ->select('classes.*')
                                    ->leftJoin('tutor_session', '`classes`.`id` = `tutor_session`.`class`')
                                    //->where(['classes.school' => 3])
                                    ->where(['classes.school_id' => Utility::getSchoolUserId()])
                                    ->where(['tutor_session.is_school' => 1])
                                    ->where(['tutor_session.status' => 3])
                                    ->all()
                                );

        return [
            'code' => '200',
            'message' => 'successful',
                'data' => [
                    'allHomework' => $allHomeWorkCount,
                    'pastHomework' => $pastHomework,
                    'activeHomeWork' => $activeHomeWork,
                    'yetToStartHomeWork' => $yetToStartHomeWork,
                    'homeworkRange' => $homeworkRange,
                    'liveClassSessions' => $liveClassSessions,
                    'pendingSessions' => $pendingSessions,
                    'ongoingSessions' => $ongoingSessions,
                    'completedSessions' => $completedSessions 
                ]
            ];
    }
}