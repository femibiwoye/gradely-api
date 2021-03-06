<?php

namespace app\modules\v2\models;

use Yii;
use yii\web\UploadedFile;

/**
 * This is the model class for table "user_profile".
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $dob
 * @property int|null $mob
 * @property int|null $yob
 * @property string|null $gender
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $signup_source
 * @property string $created_at
 *
 * @property User $user
 */
class UserProfile extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_profile';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'dob', 'mob', 'yob'], 'integer'],
            [['created_at', 'signup_source'], 'safe'],
            [['gender'], 'string', 'max' => 50],
            [['address', 'postal_code', 'about'], 'string', 'max' => 255],
            [['city', 'state', 'country'], 'string', 'max' => 100],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['birth_date'] = function ($model) {
            return !empty($model->dob) && !empty($model->mob) && !empty($model->yob) ? date('d-m-Y', strtotime("{$model->dob}-{$model->mob}-{$model->yob}")) : null;
        };
        unset($fields['dob'], $fields['mob'], $fields['yob']);

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'dob' => 'Dob',
            'mob' => 'Mob',
            'yob' => 'Yob',
            'gender' => 'Gender',
            'address' => 'Address',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function imageProcessing()
    {
        if (!isset($this->image)) {
            return true;
        }

        $img = UploadedFile::getInstance($this->user, 'image');
        $imageName = 'user_' . $this->user->id . '.' . $img->getExtension();

        $user = User::findOne($this->user->id);

        if ($user->image) {

            unlink(\Yii::getAlias('@webroot') . '/images/users/' . $user->image);

            $user->image = $this->image;
            $user->save();
        }
        $img->saveAs(Yii::getAlias('@webroot') . '/images/users/' . $imageName);
        return $this->image = $imageName;
    }
}
