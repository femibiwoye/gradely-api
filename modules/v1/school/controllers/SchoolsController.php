<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\filters\{AccessControl,VerbFilter,ContentNegotiator};
use yii\web\Controller;
use yii\web\Response;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use app\helpers\Utility;
use app\modules\v1\models\{SchoolCalendar,Homeworks,HomeworkQuestions,TutorSession,UserProfile,SchoolCurriculum,SchoolClassCurriculum};
/**
 * Schools controller
 */
class SchoolsController extends ActiveController
{
    public $modelClass = 'api\models\User';

    private $request;

    public function beforeAction($action)
    {
        
        return parent::beforeAction($action);
    }
    
    /**
     * {@inheritdoc}
     */

    public function behaviors()
    {
        return [
            [
              'class' => \yii\ filters\ ContentNegotiator::className(),
              //'only' => ['index', 'view'],
              'formats' => [
                'application/json' => \yii\ web\ Response::FORMAT_JSON,
              ],
  
  
            ],

            'authenticator' => [
                'class' => HttpBearerAuth::className(),
                'only' => ['generate-class'],
                'only' => ['create-class'],
                'only' => ['view-class'],
                'only' => ['delete-class'],
                'only' => ['list-parents'],
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


        return [
            [
              'class' => \yii\ filters\ ContentNegotiator::className(),
              'formats' => [
                'application/json' => \yii\ web\ Response::FORMAT_JSON,
              ],
            ],
            
          ];
    }


    public function getBearerToken(){

        $headers = Yii::$app->request->headers;
        $getTokenValue =  explode('Bearer', $headers->get('Authorization'));
        $removeSpacesFromToken =  trim($getTokenValue[1]," ");
        return $removeSpacesFromToken;
    }

    public function getSchoolUserId(){

        $getUser = User::findOne(['auth_key' => $this->getBearerToken()]);
        
        return $getUser->id;
    }

    public function getSchoolName(){

        $getSchool = Schools::findOne(['user_id' => Utility::getUserId()]);
        
        return $getSchool->name;
    }

    public function abreviate($schoolSlug){

        $abbr = explode('-', $schoolSlug, 2);
        $str2 = isset($abbr[1]) ? substr($abbr[1], 0, 2) : '';
        $str1 = !empty($str2) ? substr($abbr[0], 0, 3) . $str2 : substr($abbr[0], 0, 5);
        return strtoupper($str1);
    }

    public function getSchoolType($schoolType, $format){
        
        if($format == 'grade 1-12'){
            $classStart = ""; $classEnd = "";
            if($schoolType == 'primary'){   
                $classStart = "1"; $classEnd = "6";
            }
            elseif($schoolType == 'secondary'){
                $classStart = 7; $classEnd = 12;
            }
            elseif($schoolType == 'primary-secondary'){
                $classStart = "1"; $classEnd = "12";
            }
            //$i= 1;
            for($i = $classStart; $i <= $classEnd; $i++){
                $classes = new Classes();
                $classes->school_id = $this->getSchoolUserId();
                $classes->global_class_id = $i;
                $classes->slug = 'Year-'.$i;
                $classes->class_name =  'Year '.$i;
                $classes->abbreviation =  'y'.$i;
                $classes->class_code = $this->abreviate($this->getSchoolName()).'/YEAR'.$i;
                $classes->save();
            }
            return $classes;
        }



        if($format == 'year 1-12'){
        
            $classStart = ""; $classEnd = ""; $slugPrepend = "";
            if($schoolType == 'primary'){   

                $classStart = "1"; $classEnd = "6"; $slugPrepend = "Primary";

                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = $slugPrepend.$i;
                    $classes->class_name =  $slugPrepend.$i;
                    $classes->abbreviation =  $slugPrepend.$i;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'/YEAR'.$i;
                    $classes->save();
                }

                return $classes;
            }
            elseif($schoolType == 'secondary'){
                $classStart = "7"; $classEnd = "9";
                $year = 1;
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'junior-secondary-school-'.$year.'-'.Utility::getSchoolId();
                    $classes->class_name =  'Junior Secondary School-'.$year;
                    $classes->abbreviation =  'jss'.$year;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'JSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;

                $year = 1;
                $classStart = "10"; $classEnd = "12";
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'senior-secondary-school-'.$year.'-'.Utility::getSchoolId();
                    $classes->class_name =  'Senior Secondary School-'.$year;
                    $classes->abbreviation =  'sss'.$year;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'SSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;
            }

            elseif($schoolType == 'primary-secondary'){

                $classStart = "1"; $classEnd = "6"; $slugPrepend = "Primary";

                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = $slugPrepend.$i;
                    $classes->class_name =  $slugPrepend.$i;
                    $classes->abbreviation =  $slugPrepend.$i;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'/YEAR'.$i;
                    $classes->save();
                }
                return $classes;

                $classStart = "7"; $classEnd = "9";
                $year = 1;
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'junior-secondary-school-'.$year.'-'.Utility::getSchoolId();
                    $classes->class_name =  'Junior Secondary School-'.$year;
                    $classes->abbreviation =  'jss'.$year;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'JSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;

                $year = 1;
                $classStart = "10"; $classEnd = "12";
                for($i = $classStart; $i <= $classEnd; $i++){
                    $classes = new Classes();
                    $classes->school_id = Utility::getSchoolId();
                    $classes->global_class_id = $i;
                    $classes->slug = 'senior-secondary-school-'.$year.'-'.Utility::getSchoolId();
                    $classes->class_name =  'Senior Secondary School-'.$year;
                    $classes->abbreviation =  'sss'.$year;
                    $classes->class_code = $this->abreviate($this->getSchoolName()).'SSS'.$year.'';
                    $classes->save();
                    $year++;
                }
                return $classes;
            }
        }
    }

    public function getSchoolId(){

        $getSchoolUserId = Schools::find()->where(['user_id' => Utility::getUserId()])->one();

        if(!empty($getSchoolUserId)){
                return $getSchoolUserId->id;
        }

        return[
            'code' => 404,
            'message' => "Could not get school ID"
        ];
    }

    public function actionListParents(){

        //get all parents
        $getAllParents = Parents::find()
                ->select('parents.*')
                ->leftJoin('student_school', '`student_school`.`student_id` = `parents`.`student_id`')
                ->where(['student_school.school_id' => Utility::getSchoolId()])
                ->all();

        $parentChildren = [];
        if(!empty($getAllParents)){
            foreach($getAllParents as $getParent){

                //get children id from parent
                $parentChildren = $getParent->student_id;

                $getparentChildrenInfos = User::find()->where(['id' => $getParent->student_id])->all();
                //get parent info
                $getParentUserInfos = User::find()->where(['id' => $getParent->parent_id])->all();
            }

            //parentinfo
            foreach($getParentUserInfos as $getParentUserInfo){

                $getParentUserInfoAll = $getParentUserInfo;
            }

            //child/childreninfo
            foreach($getparentChildrenInfos as $getparentChildrenInfo){

                $getparentChildrenInfoAll = $getparentChildrenInfo;
            }

            Yii::info('[School parent listing succesful] School ID:'.Utility::getSchoolId());
            return[
                'code' => 200,
                'message' => "School parent listing succesful",
                'data' => [
                    'parent_id' => $getParentUserInfoAll->id,
                    'parent_name' => $getParentUserInfoAll->firstname.' '.$getParentUserInfoAll->lastname,
                    'phone' => $getParentUserInfoAll->phone,
                    'email' => $getParentUserInfoAll->email,
                    'image_url' => $getParentUserInfoAll->image,
                    'parent_relationship' => $getParentUserInfoAll->role,
                    'children' =>[
                        'child_name' => $getparentChildrenInfoAll->firstname.' '.$getparentChildrenInfoAll->lastname,
                        'image_url' => $getparentChildrenInfoAll->image
                        //TODO
                        //also pass child/children class and parent relationship
                    ]
                ]
            ];
        }
    }

    public function actionViewUserProfile(){

        $getLoginResponse = Utility::getLoginResponseByBearerToken();
        if(!empty($getLoginResponse)){
            return $getLoginResponse;
        }
    }

    public function actionEditUserProfile(){

        try{
            $getUserInfo = User::findOne(['auth_key' => Utility::getBearerToken()]);
            $getUserInfo->firstname = $this->request['firstname'];
            $getUserInfo->lastname = $this->request['lastname'];
            $getUserInfo->phone = $this->request['phone'];
            $getUserInfo->email = $this->request['email'];
            $getUserInfo->save();
            Yii::info('School user profile update successful');
            return[
                'code' => '200',
                'message' => 'School user profile update successful'
            ];
        }
        catch(Exception $exception){
            Yii::info('[School user profile update] Error:'.$exception->getMessage().'');
            return[
                'code' => '500',
                'message' => $exception->getMessage()
            ];
        }
    }

    //get schools details from the school table
    public function actionViewSchoolProfile(){

        $getUserId = Utility::getUserId();
        $getSchoolInfo = Schools::findOne(['user_id' => $getUserId]);
        if(!empty($getSchoolInfo)){
            return [
                'code '=> '200',
                'message '=> 'success',
                'data '=> $getSchoolInfo
            ];
        }
        return [
            'code '=> '200',
            'message '=> 'School not found'
        ];
    }


    public function actionEditSchoolProfile(){

        try{
            $getSchoolInfo = Schools::findOne(['user_id' => Utility::getUserId()]);
            if(!empty($getSchoolInfo)){
                $getSchoolInfo->name = $this->request['name'];
                $getSchoolInfo->about = $this->request['about'];
                $getSchoolInfo->address = $this->request['address'];
                $getSchoolInfo->city = $this->request['city'];
                $getSchoolInfo->state = $this->request['state'];
                $getSchoolInfo->country = $this->request['country'];
                $getSchoolInfo->postal_code = $this->request['postal_code'];
                $getSchoolInfo->website = $this->request['website'];
                $getSchoolInfo->phone = $this->request['phone'];
                $getSchoolInfo->school_email = $this->request['school_email'];
                $getSchoolInfo->save();
                Yii::info('School profile update successful');
                return[
                    'code' => '200',
                    'message' => 'update successful'
                ];
            }

            return[
                'code' => '200',
                'message' => 'school not found'
            ];

        }
        catch(Exception $exception){
            Yii::info('[School profile update] Error:'.$exception->getMessage().'');
            return[
                'code' => '500',
                //'message' => $exception->getMessage()
            ];
        }
    }


    //get schools details from the school table
    public function actionViewSchoolCalendar(){

        $getUserId = Utility::getUserId();
        $getSchoolInfo = Schools::findOne(['user_id' => $getUserId]);
        if(!empty($getSchoolInfo)){

            $getSchoolCalendarInfo = SchoolCalendar::findOne(['school_id' => $getSchoolInfo->id]);

            return [
                'code '=> '200',
                'message '=> 'success',
                'data '=> $getSchoolCalendarInfo
            ];
        }
        return [
            'code '=> '200',
            'message '=> 'school calendar not found'
        ];
    }


    public function actionEditSchoolCalendar(){

        try{
            $getUserId = Utility::getUserId();
            $getSchoolInfo = Schools::findOne(['user_id' => $getUserId]);
            if(!empty($getSchoolInfo)){
                $getSchoolCalendarInfo = SchoolCalendar::findOne(['school_id' => $getSchoolInfo->id]);
                $getSchoolCalendarInfo->session_name = $this->request['session_name'];
                $getSchoolCalendarInfo->year = $this->request['year'];
                $getSchoolCalendarInfo->first_term_start = $this->request['first_term_start'];
                $getSchoolCalendarInfo->first_term_end = $this->request['first_term_end'];
                $getSchoolCalendarInfo->second_term_start = $this->request['second_term_start'];
                $getSchoolCalendarInfo->second_term_end = $this->request['second_term_end'];
                $getSchoolCalendarInfo->third_term_start = $this->request['third_term_start'];
                $getSchoolCalendarInfo->third_term_end = $this->request['third_term_end'];
                $getSchoolCalendarInfo->status = $this->request['status'];
                $getSchoolCalendarInfo->save();
                Yii::info('School profile calendar update successful');
                return[
                    'code' => '200',
                    'message' => 'School profile calendar update successful'
                ];
            }
            return[
                'code' => '200',
                'message' => 'school not found'
            ];
        }
        catch(Exception $exception){
            Yii::info('[School profile calendar update] Error:'.$exception->getMessage().'');
            return[
                'code' => '500',
                //'message' => $exception->getMessage()
            ];
        }
    }

    public function actionSummaries(){
        
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

    public function actionInviteTeachers(){

        $inviteLogs  = new InviteLogs();
        try{
            $inviteLogs->receiver_name = $this->request['name'];
            $inviteLogs->receiver_email = $this->request['email'];
            $inviteLogs->receiver_phone = $this->request['phone'];
            $inviteLogs->receiver_class = $this->request['class'];
            $inviteLogs->receiver_subject = $this->request['subject_id'];
            $inviteLogs->sender_type = 'school';
            $inviteLogs->receiver_type = 'teacher';
            $inviteLogs->sender_id = Utility::getUserId();
            $inviteLogs->save();
            return[
                'code' => '200',
                'message' => '',
                'data' => $inviteLogs
            ];
        }
        catch(Exception $exception){
            return[
                'code' => '500',
                'message' => $exception->getMessage()
            ];
        }

    }

    //get schools invited teachers under school
    public function actiongetAllTeachers(){

        $getAllTeachersInvited = Invitlogs::findAll(['sender_id' => Utility::getUserId()]);
        if(!empty($invitlogs)){

            return [
                'code '=> '200',
                'message '=> 'success',
                'data '=> $getAllTeachersInvited
            ];
        }
    }

    //get schools invited teachers under school
    public function actiongetSingleTeachers($id){

        $getSingleTeachersInvited = Invitlogs::findOne(['id' => $id]);
        if(!empty($invitlogs)){

            return [
                'code '=> '200',
                'message '=> 'success',
                'data '=> $getSingleTeachersInvited
            ];
        }
    }

    public function actionAddStudents(){
        
        $user = new User();
        $splitStudentName = explode(',',$this->request['student_names']);
        $user->firstname = $splitStudentName[0];
        $user->lastname = $splitStudentName[1];
        $user->setPassword($this->request['password']);
        $user->type = '1';
        $user->auth_key = $user->generateAuthKey();

        if ($user->save()) {
                $studentSchool = new StudentSchool();
                $studentSchool->student_id = $user->id;
                $studentSchool->school_id = Utility::getSchoolId();
                $studentSchool->class_id = $this->request['class_id'];

                try{
                    $studentSchool->save();

                    $userProfile = new UserProfile();
                    $userProfile->user_id = $user->id;
                    $userProfile->save();
                    return[
                        'code' =>'200',
                        'message' => 'student succesfully added',
                        'data' => $userProfile
                    ];
                }
                catch(Exceprion $exception){

                    return[
                        'code' =>'500',
                        'message' => $exception->getMessage()
                    ];
                }
        }
    }

    public function actionListStudentsClass($id){

       $getStudents =  User::find()
                    ->select('user.*')
                    ->leftJoin('student_school', '`student_school`.`student_id` = `user`.`id`')
                    ->where(['student_school.class_id' => $id])
                    ->where(['student_school.school_id' => Utility::getSchoolId()])
                    ->all();
        return [
            'code' => '200',
            'message' => 'student list successfull',
            'data' => $getStudents
        ];
    }


    public function actionGetClassDetails($id){
        
        $getClassDetails = Classes::findOne(['id' => $id,'school_id' => Utility::getSchoolId()]);

        if(!empty($getClassDetails)){
            return [
                'code' => '200',
                'message' => 'Class details succesfully listed',
                'data' => $getClassDetails
            ];
        }
        return [
            'code' => '200',
            'message' => 'It seems class ID provided doesnt belong to this school',
        ];
     }

    public function actionChangeStudentClass($id){
    
        $getStudent = StudentSchool::findOne(['student_id' => $id]);

        if(!empty($getStudent)){

            if($this->checkIfStudentInSchool($getStudent->school_id) == true){

                try{

                    $getStudent->class_id = $this->request['new_class'];
                    $getStudent->save();
                    return [
                        'code' => '200',
                        'message' => 'Student class succesfully updated'
                    ];
                }
                catch(Exception $exception){
                    return [
                        'code' => '500',
                        'message' => $exception->getMessage()
                    ];
                }
            }

            return [
                'code' => '404',
                'message' => 'Student doesnt belong to your school',
            ];
        }
        return [
            'code' => '404',
            'message' => 'Student doesnt exist',
        ];
    }


    public function actionRemoveChildClass($id){
    
        $getStudent = StudentSchool::findOne(['student_id' => $id]);

        if(!empty($getStudent)){

            if($this->checkIfStudentInSchool($getStudent->school_id) == true){

                try{

                    $getStudent->class_id = "";
                    $getStudent->save();
                    return [
                        'code' => '200',
                        'message' => 'Student succesfully removed from class'
                    ];
                }
                catch(Exception $exception){
                    return [
                        'code' => '500',
                        'message' => $exception->getMessage()
                    ];
                }
            }

            return [
                'code' => '404',
                'message' => 'Student doesnt belong to your school',
            ];
        }
        return [
            'code' => '404',
            'message' => 'Student doesnt exist',
        ];
    }

     private function checkIfStudentInSchool($studentSchoolId){

        if($studentSchoolId = Utility::getSchoolId()){
            return true;
        }
        else{
            return false;
        }
     }

    public function actionSettingsUpdateEmail(){

        $model = new User();
        $user = User::findOne(['email'=> $this->request['email'], 'password' => $model->validatePassword($this->request['password'])]);
        if(!empty($user)){

            try{
                $user->email = $this->request['new_email'];
                $user->save();

                return [
                    'code' => '200',
                    'message' => 'Email succesfully updated'
                ];
            }
            catch(Exception $exception){
                return[
                    'code' => '500',
                    'message' => $exception->getMessage()
                ];
            }
        }
        return [
            'code' => '404',
            'message' => 'email or password incorrect'
        ];
    }

    public function actionSettingsUpdatePassword(){

        $model = new User();
        $user = User::findOne(['email'=> $this->request['email'], 'password' => $model->validatePassword($this->request['password'])]);
        if(!empty($user)){

            try{
                $user->password = $user->setPassword($this->request['password']);
                $user->save();

                return [
                    'code' => '200',
                    'message' => 'Password succesfully updated'
                ];
            }
            catch(Exception $exception){
                return[
                    'code' => '500',
                    'message' => $exception->getMessage()
                ];
            }
        }
        return [
            'code' => '404',
            'message' => 'email or password incorrect'
        ];
    }

    public function actionSettingsDeleteAccount(){
    
        $model = new User();
        $user = User::findOne(['user_id' => Utility::getUserId(), 'password' => $model->validatePassword($this->request['password'])]);
        if(!empty($user)){

            try{
                $user->status = 0;
                $user->auth_key = '';
                $user->email = $user->email.'-deleted';
                $user->phone = $user->phone.'-deleted';
                $user->save();

                return [
                    'code' => '200',
                    'message' => 'Password succesfully deleted'
                ];
            }
            catch(Exception $exception){
                return[
                    'code' => '500',
                    'message' => $exception->getMessage()
                ];
            }
        }
    }

    public function actionSettingsListCurriculum(){

        $getCurriculum = SchoolCurriculum::find()
                            ->select('school_curriculum.*')
                            ->leftJoin('exam_type', '`exam_type`.`id` = `school_curriculum`.`curriculum_id`')
                            ->where(['school_curriculum.school_id' => Utility::getSchoolUserId()])
                            ->all();
        if(!empty($getCurriculum)){
            return[
                'code' => '200',
                'message' => 'curriculum listing sucessful',
                'data' => $getCurriculum
            ];
        }
        return[
            'code' => '404',
            'message' => 'couldnt find any curriculum for this school',
        ];
    }

    public function actionSettingsUpdateCurriculum(){

        $getCurriculum = SchoolCurriculum::find()->where(['school_id' => Utility::getSchoolId()])->all();
        if(!empty($getCurriculum)){

            try{
                $getCurriculum->curriculum_id = $this->request['curriculum_id'];
                $getCurriculum->save();
                return[
                    'code' => '200',
                    'message' => 'school curriculum successfully updted',
                ];
            }
            catch(Exception $exception){

                return[
                    'code' => '500',
                    'message' => $exception->getMessage()
                ];
            }
        }
        return[
            'code' => '404',
            'message' => 'couldnt find any curriculum for this school',
        ];
    }

    public function actionSettingsRequestNewCurriculum(){

        try{
            $sendmMailToAdmin = Yii::$app->mailer->compose()
                    ->setFrom(Yii::$app->params['notificationSentFromEmail'])
                    ->setTo(Yii::$app->params['adminEmail'])
                    ->setSubject(Yii::$app->params['newlySugestedCurriculumSubject'])
                    ->setHtmlBody('
                    
                        <b>Hello,</b>

                        The curriculum below was suggested
                        Curriculum Name: '.$this->request['curriculum'].'
                        Country: '.$this->request['country'].'
                        Comments: '.$this->request['comments'].'
                    
                    ')
                    ->send();

                    return[
                        'code' => '200',
                        'message' => 'Curriculum succesfully requested'
                    ];
        }
        catch( Exception $exception){
            return[
                'code' => '200',
                'message' => $exception->getMessage()
            ];
        }
    }


    public function actionSettingsListSubjects(){

        $getSubject = SchoolSubject::findOne(['school_id' => Utility::getSchoolId()]);
        if(!empty($getSubject)){
            return[
                'code' => '200',
                'message' => 'subjects listing sucessful',
                'data' => $getSubject
            ];
        }
        return[
            'code' => '404',
            'message' => 'couldnt find any subject for this school',
        ];
    }

    public function actionSettingsUpdateSubject($id){
        
        $getSubject = SchoolSubject::findOne(['school_id' => Utility::getSchoolId(), 'id' => $id]);
        if(!empty($getSubject)){

            try{
                $getSubject->subject_id = $this->request['subject_id'];
                $getSubject->save();
                return[
                    'code' => '200',
                    'message' => 'school subject successfully updted',
                ];
            }
            catch(Exception $exception){

                return[
                    'code' => '500',
                    'message' => $exception->getMessage()
                ];
            }
        }
        return[
            'code' => '404',
            'message' => 'couldnt find the particular subject for this school',
        ];
    }

    public function actionSettingsRequestNewSubject(){

        try{
            $sendmMailToAdmin = Yii::$app->mailer->compose()

                    ->setFrom(Yii::$app->params['notificationSentFromEmail'])
                    ->setTo(Yii::$app->params['adminEmail'])
                    ->setSubject(Yii::$app->params['newlySugestedSubjectSubject'])
                    ->setHtmlBody('
                    
                        <b>Hello,</b>

                        The subject below was suggested
                        Subject Name: '.$this->request['curriculum'].'
                        Country: '.$this->request['country'].'
                        Comments: '.$this->request['comments'].'
                    
                    ')
                    ->send();

                    return[
                        'code' => '200',
                        'message' => 'subject succesfully requested'
                    ];
        }
        catch( Exception $exception){
            return[
                'code' => '200',
                'message' => $exception->getMessage()
            ];
        }
    }
}