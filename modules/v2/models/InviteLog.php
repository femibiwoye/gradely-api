<?php

namespace app\modules\v2\models;

use Yii;

class InviteLog extends \yii\db\ActiveRecord
{
	public static function tableName()
	{
		return 'invite_log';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['receiver_email', 'receiver_type', 'sender_type', 'sender_id', 'token'], 'required'],
			[['receiver_class', 'receiver_subject', 'sender_id', 'status'], 'integer'],
			[['extra_data'], 'string'],
			[['created_at'], 'safe'],
			[['receiver_email', 'receiver_name', 'receiver_subjects'], 'string', 'max' => 100],
			[['receiver_type', 'sender_type'], 'string', 'max' => 20],
			[['receiver_phone'], 'string', 'max' => 50],
			[['token'], 'string', 'max' => 200],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'receiver_email' => 'Receiver Email',
			'receiver_name' => 'Receiver Name',
			'receiver_type' => 'Receiver Type',
			'receiver_phone' => 'Receiver Phone',
			'receiver_class' => 'Receiver Class',
			'receiver_subject' => 'Receiver Subject',
			'receiver_subjects' => 'Receiver Subjects',
			'sender_type' => 'Sender Type',
			'sender_id' => 'Sender ID',
			'token' => 'Token',
			'extra_data' => 'Extra Data',
			'status' => 'Status',
			'created_at' => 'Created At',
		];
	}
}
