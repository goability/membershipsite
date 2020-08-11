<?php
/*
  A general Logger 
*/
require_once "Constants.php";

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;




/*
A logger wrapper
*/
class Log {


  private static $logger;

  public static $Name = "pwh";

  public static function init($logType){

    self::$Name   = LOG_FILE_PREFIX . $logType; //pwhSite
    self::$logger = new Logger(self::$Name);

    $PATH_LOG_INFO = join(DIRECTORY_SEPARATOR, array(LOG_BASEPATH, self::$Name . "_INFO" . LOG_FILE_EXT));
    $PATH_LOG_WARN = join(DIRECTORY_SEPARATOR, array(LOG_BASEPATH, self::$Name . "_WARN" . LOG_FILE_EXT));
    $PATH_LOG_ERROR = join(DIRECTORY_SEPARATOR, array(LOG_BASEPATH, self::$Name . "_ERROR" . LOG_FILE_EXT));
    $PATH_LOG_DEBUG = join(DIRECTORY_SEPARATOR, array(LOG_BASEPATH, self::$Name . "_DEBUG" . LOG_FILE_EXT));


    self::$logger->pushHandler(new RotatingFileHandler($PATH_LOG_INFO, MAX_LOGS_INFO,  Logger::INFO));
    self::$logger->pushHandler(new RotatingFileHandler($PATH_LOG_WARN, MAX_LOGS_WARN, Logger::WARNING));
    self::$logger->pushHandler(new RotatingFileHandler($PATH_LOG_ERROR,  MAX_LOGS_ERROR, Logger::ERROR));
    self::$logger->pushHandler(new RotatingFileHandler($PATH_LOG_DEBUG,  MAX_LOGS_DEBUG, Logger::DEBUG));

  }
  public static function info($msg){
    self::$logger->info($msg);
  }
  public static function error($msg){
        self::$logger->err($msg);
  }
  public static function warning($msg){
        self::$logger->warn($msg);
  }
  public static function debug($msg){
        self::$logger->debug($msg);
  }
}

if (isset($_SERVER)){
  //CREATE THE LOG INSTANCE
  Log::init('Site');
}
