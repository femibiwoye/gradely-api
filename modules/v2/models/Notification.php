<?php


namespace app\modules\v2\models;


use yii\base\Model;

class Notification extends Model
{


    private function welcome_school(Notifications $model)
    {

        $user = User::findOne(['id' => $model->notificationActionDatas[0]->field_value]);

        $school = Schools::findOne(['user_id' => $user->id]);
        if(NotificationOutLogging::find()->where(['notification_id' => $model->id, 'notification_type' => 'email', 'status' => 1])->exists() == false) {

            // For email sending
            $notificationLog = $model->CreateOutLogging($model, $school->id, 'email');
            $email = SendEmail::widget([
                'template' => "school/$model->action_name",
                'subject' => 'Welcome to Gradely!',
                'to' => $school->school_email,
                'data' => ['user' => $school,
                    'cta' => GenerateLinks::widget([
                        'destination' => $this->domain . '/site/verify-email?token=' . $user->verification_token,
                        'outingID' => $notificationLog->id,
                        'long' => true,
                        'notificationID' => $model->id
                    ])]
            ]);
            if ($email)
                $model->UpdateOutLogging($notificationLog->id);
        }

        /*================================================
                       In-App Notification
        =================================================*/

        if(NotificationOutLogging::find()->where(['notification_id' => $model->id, 'notification_type' => 'app', 'status' => 1])->exists() == false) {
            $notificationLog = $model->CreateOutLogging($model, $school->id, 'app');

            if($notificationLog) {

                /*Saves to in-app-log*/
                $action_id = $model->notificationActionDatas[0]->action_id;

                $notificationMsg = NotificationMessages::find()->where([
                    'type' => 'app',
                    'action_id' => $action_id
                ])->one();

                $message = str_replace('[School Name]', $school->name, $notificationMsg->message);

                $inappLog = new InappNotification();
                $inappLog->notification_id = $notificationLog->notification_id;
                $inappLog->out_logging_id = $notificationLog->id;
                $inappLog->user_id = $notificationLog->receiver_id;
                $inappLog->message = $message;
                if($inappLog->save())
                    //Updates the log status to sent
                    $model->UpdateOutLogging($notificationLog->id);
            }
        }


        if (NotificationOutLogging::find()->where(['notification_type' => ['email', 'app'],'status' => 1,'notification_id' => $model->id,])->exists())
            $model->CompleteNotification();
    }

}