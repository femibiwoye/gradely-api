<?php
namespace app\modules\v2\models;

class Student extends User {
	public function fields() {
		return [
			'id',
			'code',
			'firstname',
			'lastname',
			'image'
		];
	}
}