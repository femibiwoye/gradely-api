<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\{SharedConstant};

class UserPreference extends \yii\db\ActiveRecord {
	/**
	 * {@inheritdoc}
	 */
	public static function tableName() {
		return 'user_preference';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules() {
		return [
			[['user_id'], 'required'],
			[['id', 'user_id', 'weekly_progress_report', 'product_update', 'offer', 'sms', 'whatsapp', 'newsletter', 'reminder'], 'integer'],
			[['weekly_progress_report', 'product_update', 'offer', 'sms', 'whatsapp', 'newsletter', 'reminder'], 'default', 'value' => SharedConstant::VALUE_ONE],
			[['updated'], 'safe'],
			[['id'], 'unique'],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels() {
		return [
			'id' => 'ID',
			'user_id' => 'User ID',
			'weekly_progress_report' => 'Weekly Progress Report',
			'product_update' => 'Product Update',
			'offer' => 'Offer',
			'sms' => 'Sms',
			'whatsapp' => 'Whatsapp',
			'newsletter' => 'Newsletter',
			'reminder' => 'Reminder',
			'updated' => 'Updated',
		];
	}

	public function fields() {
		return [
			'id',
			'user_id',
			'weekly_progress_report',
			'product_update',
			'offer',
			'sms',
			'whatsapp',
			'newsletter',
			'reminder',
			'updated',
		];
	}

	/**
	 * Gets query for [[User]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getUser() {
		return $this->hasOne(User::className(), ['id' => 'user_id']);
	}

	public function beforeSave($insert) {
		if ($this->isNewRecord) {
			$this->updated = date('Y-m-d H:i:s');;
		} else {
			$this->updated = date('Y-m-d H:i:s');;
		}

		return parent::beforeSave($insert);
	}
}
