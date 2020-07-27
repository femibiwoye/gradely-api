<?php

namespace app\modules\v2\models;

use Yii;

/**
 * This is the model class for table "timezone".
 *
 * @property int $id
 * @property string $area This is like the continent or a name that group series of timezone
 * @property string $name This is the actual name of the timezone and it is also the primary key
 */
class Timezone extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'timezone';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['area', 'name'], 'required'],
            [['area', 'name'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'area' => 'Area',
            'name' => 'Name',
        ];
    }
}
