<?php
/*
Common application constants.
DB specific configs are in dbconfig

*/
require_once('../vendor/autoload.php');

const PRODUCT_NAME = "publicwarehouse"; // used currently only for sessioning

//TURN OFF IN PRODUCTION, adds extra notifications on the screen
const DEBUG_MODE = true;

//URL to the warehouse data REST service
const API_URL       = "http://localhost:8888/api";
const SITE_URL      = "http://localhost:8888";

//Base directory for a user's resources, such as item images
const RESOURCE_DIR = "resources";


// Token Time To Live Settings:
// PASSWORD_RESET:
  //Number of seconds a password reset authCode is valid.  This is how long
  //  it would taken the user to click on the link and reset their password
  //   DEFAULT IS THREE MINUTES
//
// TOKEN_TTL_SESSION_AUTOLOGOUT:
//  Number of seconds between a site access.  Used by SessionManager Only

//TIMES ARE IN SECONDS
const TOKEN_TTL_PASSWORD_RESET      = 180; //three minutes
const TOKEN_TTL_SESSION_AUTOLOGOUT  = 300; //five minutes without a refresh

// EMAIL Settings
const EMAIL_FROM_DOMAIN = "mattchandler.us";
const EMAIL_FROM_USER_NOREPLY = "no-reply";

//--- LOGGING ---

// Log File Paths - Change for windows/linux as needed
const LOG_BASEPATH    = "/var/log/pwh"; //i.e. "c:\websites\logs"
const LOG_FILE_PREFIX = "pwh"; //prefix for all files pwh{site;api}INFO.log, pwh{site;api}ERROR.log
const LOG_FILE_EXT    = ".log";//file extension

// Number of files to keep after rotated
const MAX_LOGS_INFO   = 5;
const MAX_LOGS_WARN   = 5;
const MAX_LOGS_ERROR  = 5;
const MAX_LOGS_DEBUG  = 5;


//-- DATABASE SETTINGS

const DATABASE_MYSQL      = "MySQL";
//// NOTE: PostgreSQL support NOT updated in a while, needs work
const DATABASE_POSTGRESQL = "PostgreSQL";

//Configurable settings
const MAX_RECORD_LIMIT  = 1000; //max number of records to return
//SET DATABASE TYPE HERE
const DATABASE_TYPE     = DATABASE_MYSQL; //DATABASE_POSTGRESQL or DATABASE_MYSQL


// -- POSTGRESQL SPECIFIC
const DATABASE_PG_NAME      = 'warehouse'; // used as prefix for queries
const DATABASE_PG_USER      = 'pwh_admin';
const DATABASE_PG_PASSWORD  = 'password2';
const DATABASE_PG_PORT      = '5432';
const DATABASE_PG_HOST      = '127.0.0.1';

// -- MYSQL SPECIFIC
const DATABASE_MYSQL_NAME          = "warehouse"; // used as prefix for queries
const DATABASE_MYSQL_USER          = "warehouse_admin";
const DATABASE_MYSQL_PASSWORD      = "password1";
const DATABASE_MYSQL_HOST          = "127.0.0.1";
const DATABASE_MYSQL_PORT          = "3308";

// SESSIONING
const SESSION_NAME                 = PRODUCT_NAME;


//-- HTTP CODES
const HTTP_CODES = array(
      100 => 'Continue',
      101 => 'Switching Protocols',
      102 => 'Processing',
      103 => 'Checkpoint',
      200 => 'OK',
      201 => 'Created',
      202 => 'Accepted',
      203 => 'Non-Authoritative Information',
      204 => 'No Content',
      205 => 'Reset Content',
      206 => 'Partial Content',
      207 => 'Multi-Status',
      300 => 'Multiple Choices',
      301 => 'Moved Permanently',
      302 => 'Found',
      303 => 'See Other',
      304 => 'Not Modified',
      305 => 'Use Proxy',
      306 => 'Switch Proxy',
      307 => 'Temporary Redirect',
      400 => 'Bad Request',
      401 => 'Unauthorized',
      402 => 'Payment Required',
      403 => 'Forbidden',
      404 => 'Not Found',
      405 => 'Method Not Allowed',
      406 => 'Not Acceptable',
      407 => 'Proxy Authentication Required',
      408 => 'Request Timeout',
      409 => 'Conflict',
      410 => 'Gone',
      411 => 'Length Required',
      412 => 'Precondition Failed',
      413 => 'Request Entity Too Large',
      414 => 'Request-URI Too Long',
      415 => 'Unsupported Media Type',
      416 => 'Requested Range Not Satisfiable',
      417 => 'Expectation Failed',
      418 => 'I\'m a teapot',
      422 => 'Unprocessable Entity',
      423 => 'Locked',
      424 => 'Failed Dependency',
      425 => 'Unordered Collection',
      426 => 'Upgrade Required',
      449 => 'Retry With',
      450 => 'Blocked by Windows Parental Controls',
      500 => 'Internal Server Error',
      501 => 'Not Implemented',
      502 => 'Bad Gateway',
      503 => 'Service Unavailable',
      504 => 'Gateway Timeout',
      505 => 'HTTP Version Not Supported',
      506 => 'Variant Also Negotiates',
      507 => 'Insufficient Storage',
      509 => 'Bandwidth Limit Exceeded',
      510 => 'Not Extended'
  );
