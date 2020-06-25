<?php
namespace app\modules\v2\models;

use Yii;

/**
 * Signup form
 */
class ApiResponse {
	public $name;
	public $message;
	public $code;
	public $data;

	const UNKNOWN_RESPONSE = 0;
	const SUCCESSFUL = 200;
	const UNAUTHORIZED = 401;
	const STILL_UNDER_CONSTRUCTION = 404;
	const UNABLE_TO_PERFORM_ACTION = 406;
	const ALREADY_TAKEN = 409;
	const EXPECTATION_FAILED = 417;
	const RE_INVITE = 491;
	const UNKNOWN_ERROR = 666;
	const UNKNOWN = 999;
	const PRE_CONDITION_REQUIRED = 428;
	const FORBIDDEN = 403;
	const PRECONDITION_FAILED = 422;

	private $codes = [
		self::UNKNOWN_RESPONSE => "Unknown response",
		self::SUCCESSFUL => "Success",
		self::UNAUTHORIZED => "Unauthorized",
		self::STILL_UNDER_CONSTRUCTION => "Still under construction",
		self::UNABLE_TO_PERFORM_ACTION => "Unable to perform action"
	];

	function message($name=null, $message=null, $code=null, $models=null) {
		$this->name = $name;
		$this->message = $message? $message:$this->getMessage($code);
		$this->code = $code? $code:999;
		$this->data = $models;

		return $this;
	}

	function success($models=null, $code=null, $message=null) {
		return $this->message("Success", $message, $code? $code:self::SUCCESSFUL, $models);
	}

	function error($models=null, $code=null, $message=null) {
		return $this->message("Error", $message, $code? $code:self::EXPECTATION_FAILED, $models);
	}

	function underconstruction() {
		return $this->error(null, self::STILL_UNDER_CONSTRUCTION);
	}

	function unauthorized() {
		return $this->error(null, self::UNAUTHORIZED);
	}

	function getMessage($code=0) {
		return $this->codes[$code];
	}
}
