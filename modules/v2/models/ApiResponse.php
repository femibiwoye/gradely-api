<?php

namespace app\modules\v2\models;

use Yii;

/**
 * Signup form
 */
class ApiResponse
{
    public $name;
    public $message;
    public $code;
    public $data;


    const UNKNOWN_RESPONSE = 0;
    const CONTINUE_REQUEST = 100;
    const SWITCHING_PROTOCOL = 101;
    const PROCESSING_REQUEST = 102;
    const CONNECTION_TIMED_OUT = 118;
    const SUCCESSFUL = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NON_AUTHORITATIVE = 203;
    const NO_CONTENT = 204;
    const RESET_CONTENT = 205;
    const PARTIAL_CONTENT = 206;
    const MULTI_STATUS = 207;
    const ALREADY_REPORTED = 208;
    const CONTENT_DIFFERENT = 210;
    const IM_USED = 226;
    const MULTIPLE_CHOICES = 300;
    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const SEE_OTHER = 303;
    const NOT_MODIFIED = 304;
    const USE_PROXY = 306;
    const TEMPORARY_REDIRECT = 307;
    const PERMANENT_REDIRECT = 308;
    const TOO_MANY_REDIRECT = 310;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const PAYMENT_REQUIRED = 402;
    const REQUEST_FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const UNABLE_TO_PERFORM_ACTION = 406;
    const PROXY_AUTHENTICATION_REQUIRED = 407;
    const REQUEST_TIME_OUT = 408;
    const ALREADY_TAKEN = 409;
    const REQUEST_GONE = 410;
    const LENGTH_REQUIRED = 411;
    const PRECONDITION_FAILED = 412;
    const REQUEST_ENTITY_TOO_LARGE = 413;
    const REQUEST_URI_TOO_LONG = 414;
    const UNSUPPORTED_MEDIA_TYPE = 415;
    const REQUEST_RANGE_UNSATISFIABLE = 416;
    const EXPECTATION_FAILED = 417;
    const MISDIRECTED_REQUEST = 421;
    const UNPROCESSABLE_ENTITY = 422;
    const LOCKED = 423;
    const UNORDERED_COLLECTION = 425;
    const UPGRADE_REQUIRED = 426;
    const PRE_CONDITION_REQUIRED = 428;
    const TOO_MANY_REQUESTS = 429;
    const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    const RE_INVITE = 491;
    const RETRY_WITH = 499;
    const BLOCKED_BY_WINDOWS_PARENTAL_CONTROL = 450;
    const UNBELIEVABLE_FOR_LEGAL_REASONS = 451;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const BAD_GATEWAY_OR_PROXY_ERROR = 502;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIME_OUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const INSUFFICIENT_STORAGE = 507;
    const BANDWIDTH_LIMIT_EXCEEDED = 509;
    const NOT_EXTENDED = 510;
    const NETWORK_AUTHENTICATION_REQUIRED = 511;
    const UNKNOWN_ERROR = 666;


    private $codes = [
        self::UNKNOWN_RESPONSE => "Unknown response",
        self::SUCCESSFUL => "Success",
        self::UNAUTHORIZED => "Unauthorized",
        self::NOT_FOUND => "Not found",
        self::UNABLE_TO_PERFORM_ACTION => "Unable to perform action",
        self::REQUEST_GONE => "Request no longer exist",
        self::NON_AUTHORITATIVE => "No authority for this request",
    ];

    function message($name = null, $message = null, $code = null, $models = null)
    {
        $this->name = $name;
        $this->message = $message ? $message : $this->getMessage($code);
        $this->code = $code ? $code : 999;
        $this->data = $models;

        return $this;
    }

    function success($models = null, $code = null, $message = null)
    {
        //Yii::$app->response->statusCode = $code;
        return $this->message("success", $message, $code ? $code : self::SUCCESSFUL, $models);
    }

    function error($models = null, $code = null, $message = null)
    {
        //Yii::$app->response->statusCode = $code;
        return $this->message("error", $message, $code ? $code : self::EXPECTATION_FAILED, $models);
    }

    function underconstruction()
    {
        //Yii::$app->response->statusCode = self::STILL_UNDER_CONSTRUCTION;
        return $this->error(null, self::STILL_UNDER_CONSTRUCTION);
    }

    function unauthorized()
    {
        //Yii::$app->response->statusCode = self::UNAUTHORIZED;
        return $this->error(null, self::UNAUTHORIZED);
    }

    function getMessage($code = 0)
    {
        return $this->codes[$code];
    }
}
