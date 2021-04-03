<?php

namespace app\modules\v2\exam\components;

use yii\db\ActiveRecord;

class ExamUtility extends ActiveRecord
{
    /**
     * If student class category.
     * Student could either be in primary or secondary
     * @param $class_id
     * @return string|null
     */
    public static function StudentClassCategory($class_id)
    {
        if ($class_id >= 7 && $class_id <= 9)
            $category = 'junior';
        elseif ($class_id >= 10 && $class_id <= 12)
            $category = 'secondary';
        elseif ($class_id >= 1 && $class_id <= 6 || $class_id > 12)
            $category = 'primary';
        else
            $category = null;
        return $category;
    }

}