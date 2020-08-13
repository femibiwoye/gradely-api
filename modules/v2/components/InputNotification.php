<?php

namespace app\modules\v2\components;

use app\modules\v2\models\notifications\Actions;
use app\modules\v2\models\notifications\NotificationActionData;
use app\modules\v2\models\notifications\Notifications;
use yii\base\Model;
use yii\base\Widget;

class InputNotification extends Model
{

//    public $actionName;
//    public $receiverType;
//    public $fields;

    public function NewNotification($actionName, $receiverType, $fields)
    {
        $action = Actions::findOne(['name' => $actionName]);
        parent::init();
        $notification = new Notifications();
        $notification->action_id = $action->id;
        $notification->action_name = $actionName;
        $notification->receiver_type = $receiverType;
        if ($notification->save()) {
            foreach ($fields as $field) {
                $notModel = new NotificationActionData();
                $notModel->notification_id = $notification->id;
                $notModel->field_name = $field[0];
                $notModel->field_value = (string)$field[1];
                $notModel->action_id = $notification->action_id;
                if (!$notModel->save())
                    return false;

            }
            return true;
        }
        return false;
    }

}