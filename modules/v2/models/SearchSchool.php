<?php

namespace app\modules\v2\models;

use Yii;

class SearchSchool extends Schools
{
	public function fields() {
		return [
			'id',
			'name',
			'abbr',
		];
	}
}
