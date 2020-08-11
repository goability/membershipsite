<?php

/**
 *  Simple wrapper for session management
 */
class PWH_SessionManager
{

  public static function ResumeSession(){
    if (!isset($_SESSION)){
      Log::info("Resuming session");
      session_start();
    }
  }
  /*
  * Start a session, and load the configuration a resource
  */
  public static function StartSession($userID, $accessToken, $expiresUnixTime){
    $sessionStatus = session_status();

    if ($sessionStatus === PHP_SESSION_DISABLED){
      Log::error("Sessions are disabled.  This is a server configuration");
      echo "ERROR";
      die();
    }
    else if ($sessionStatus!==PHP_SESSION_ACTIVE)
    {
      Log::info("SESSION - STARTING SESSION -Starting session for user $userID and accessToken $accessToken");
      session_start();
    }

    $c = ConfigurationManager::GetLoadedResourceCount();
    Log::debug("Sessiong Init resource count is $c");

    //Load the configuration into session
    $loaded = self::_loadConfigIntoSession(true);

    if (!$loaded){
      session_destroy();
      echo "CONFIGURATION SETUP ERROR - There are no resources loaded.";
    }
    else{
      Log::info("Session loaded");
      $_SESSION["userID"]             = $userID;
      $_SESSION["accessToken"]        = $accessToken;
      $_SESSION["isAdministrator"]    = false;
      $_SESSION["expires_unix_time"]  = $expiresUnixTime;
    }
  }
  public static function EndSession(){
    unset($_SESSION["userID"]);
    unset($_SESSION["accessToken"]);
    unset($_SESSION["isAdministrator"]);
    unset($_SESSION["expires_unix_time"]);
    unset($_SESSION[\Warehouse\Constants\SessionVariableNames::CONFIG_SITE]);

    unset($_SESSION[\Warehouse\Constants\SessionVariableNames::RESOURCE_ACL]);
  }
  public static function SetParameter($name, $value){
    $_SESSION[$name] = $value;
  }
  public static function GetParameter($name){
    return isset($_SESSION[$name]) ? $_SESSION[$name] : 0;
  }
  public static function DeleteParameter($name){
    unset($_SESSION[$name]);
  }
  public static function IsAdministrator(){
    return self::GetParameter('isAdministrator');
  }
  public static function GetCurrentUserID(){
    return self::GetParameter('userID');
  }
  public static function GetCurrentUsername(){
    return self::GetParameter('currentUsername');
  }
  public static function GetCurrentEmailAddress(){
    return self::GetParameter('currentEmailAddress');
  }
  public static function GetAccessToken(){
    return self::GetParameter('accessToken');
  }

  /*
  * Set an associative array (keyed by ResourceName), contents:  array of recordIDs this user owns
  *
  *     [ "resourceName" =>
  *          "ACL_Data" => [
  *                 "READ_ONLY" => "15,16,17",
  *                 "UPDATE" => "18,19",
  *                 "DELETE" => "20, 21"
  *            ],
  *        ]
  *    ]
  */
  public static function SetUserResourceAccess($resourceAccessList){
    $_SESSION[\Warehouse\Constants\SessionVariableNames::RESOURCE_ACL] = $resourceAccessList;
  }
  /*
  *  Determines if current user has access to a resource and can add a new one
  */
  public static function VerifyCanAddNewRecords($resourceName){

    $resourceACL = self::GetParameter(\Warehouse\Constants\SessionVariableNames::RESOURCE_ACL);

    if ( is_null($resourceACL) || !isset($resourceACL[$resourceName]) )
    {
      Log::debug("SESSION ERROR GetUserRecordAccess - ACL or resource $resource was not found ");
      return false;
    }
    else {
        return $resourceACL[$resourceName]["canAddNewRecords"];
    }
  }
  /*
  * Determines if user has any sort of access to this resource
  */
  public static function VerifyResourceAccess($resourceName)
  {
    $resourceACL = self::GetParameter(\Warehouse\Constants\SessionVariableNames::RESOURCE_ACL);
    return !is_null($resourceACL) && array_key_exists($resourceName,$resourceACL);
  }
  /*
  *  If key does not exist, user has no CRUD at all to this resource
  */
  public static function GetAccessibleResourceNames(){

    $resourceACL = self::GetParameter(\Warehouse\Constants\SessionVariableNames::RESOURCE_ACL);

    return is_array($resourceACL) ? array_keys($resourceACL) : null;

  }
  public static function GetAccessibleRecordIDs($resourceName){

    $accessibleRecordIDs = array();
    $resourceACL = self::GetParameter(\Warehouse\Constants\SessionVariableNames::RESOURCE_ACL);

    if (!empty($resourceACL[$resourceName][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA])){
    //  var_dump($resourceACL[$resourceName][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA][\Warehouse\Constants\ResourceAccessType::FULL]);
      $accessibleRecordIDs = $resourceACL[$resourceName][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA][\Warehouse\Constants\ResourceAccessType::FULL];
    }

    // TODO: THESE ONLY ALLOW OWNED RECORDS, Associated co-owners should be allowed to update, but not delete

    return $accessibleRecordIDs;

  }
  public static function GetOwnedRecordIDs($resourceName){

    $resourceACL = self::GetParameter(\Warehouse\Constants\SessionVariableNames::RESOURCE_ACL);
    return isset($resourceACL[$resourceName][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA][\Warehouse\Constants\ResourceAccessType::FULL]) ?
    $resourceACL[$resourceName][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA][\Warehouse\Constants\ResourceAccessType::FULL] : null;
  }
  /*
  *   Determine if user has certain access to a record
  */
  public static function VerifyRecordAccess($resourceName, $recordID, $accessType){
    // TODO: Not sure about linking these in with session manager, but that is where data is and should be

    $resourceACL = self::GetParameter(\Warehouse\Constants\SessionVariableNames::RESOURCE_ACL);

    if (
        is_null($resourceACL)|| !isset($resourceACL[$resourceName])
        )
    {
      Log::debug("SESSION ERROR GetUserRecordAccess - ACL or resource $resource was not set/found ");
      return false;
    }
    else {
      $accessTypeCheck = $accessType;
      $accessGranted = false;

      do
      {
          $accessGranted =  isset($resourceACL[$resourceName]["ACL"][$accessType]) &&
                            in_array($resourceACL[$resourceName]["ACL"][$accessType], $recordID);
          $accessTypeCheck *= 2;
      }
      while( !accessGranted && $accessTypeCheck <= \Warehouse\Constants\SessionVariableNames::FULL);


      return  accessGranted;
    }
  }

  public static function VerifyCanUpdateRecord($resourceName, $recordID){
    return VerifyRecordAccess($resourceName, $recordID, \Warehouse\Constants\ResourceAccessType::UPDATE);
  }
  public static function VerifyCanViewRecords($resourceName, $recordID){
    return VerifyRecordAccess($resourceName, $recordID, \Warehouse\Constants\ResourceAccessType::UPDATE);
  }
  public static function VerifyCanDeleteRecord($resourceName, $recordID){
      return VerifyRecordAccess($resourceName, $recordID, \Warehouse\Constants\ResourceAccessType::DELETE);
  }
  public static function VerifyHasFullAccessToRecord($resourceName, $recordID){
      return VerifyRecordAccess($resourceName, $recordID, \Warehouse\Constants\ResourceAccessType::FULL);
  }

  /**
  * Verify if a Session is active:  isset(accessToken) && is correct
  * @param $accessToken - if supplied, must match, if empty simply look at session
  * @return bool
  */
  public static function IsActive($accessToken=null)
  {
    if ($accessToken===null){
      //If not provided, try to pull from GET
      $accessToken = isset($_GET['accessToken']) ? $_GET['accessToken'] : 0;
    }

    return  !self::IsExpired($accessToken) &&
             self::IsStarted() &&
            isset($_SESSION['accessToken']) &&
            $_SESSION['accessToken']===$accessToken;
  }
  /*
  * Returns true if a session is expired
  *  Extends time if not expired
  */
  public static function IsExpired($accessToken){

    $expiresUnixTime = isset($_SESSION["expires_unix_time"]) ?
                              $_SESSION["expires_unix_time"] : 0;

    $expired = time() > $expiresUnixTime;

    if (!$expired){
      $_SESSION["expires_unix_time"]  = time() + TOKEN_TTL_SESSION_AUTOLOGOUT;
      Log::info("SESSION - Extending session timout");
    }
    return $expired;
  }
  /*
    Determine if session has been started.  Does not look at any data
    @return: true/false based on session_status
  */
  public static function IsStarted(){
    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            if (session_status() === PHP_SESSION_ACTIVE){
              return true;
            }
        } else {
            return session_id() === '';
        }
    }
    return FALSE;
  }
  // CONFIGURATION
  //  Load from Configuration Manager into Session object
  private static function _loadConfigIntoSession($forceReload=false){

    $resourcesExist = ConfigurationManager::HasResources();

    if ( $forceReload || !$resourcesExist){
      $c = ConfigurationManager::GetLoadedResourceCount();
      Log::debug("===== SESSION SET ===== $c Resources were loaded from ConfigManager into session ");
      $_SESSION[\Warehouse\Constants\SessionVariableNames::CONFIG_SITE]   = ConfigurationManager::GetConfigurationObject();
      return true;
    }
    else{
      return ConfigurationManager::GetLoadedResourceCount() > 1;
    }
  }
  /*
    Returns true/false if configuration has already been loaded
  */
  public static function ConfigurationIsLoaded(){
    return isset($_SESSION[\Warehouse\Constants\SessionVariableNames::CONFIG_SITE]);
  }
  /*
  * Load the configurations if they do not exist already
  */
  public static function LoadConfigurations(){

    $sessionInConfig = isset($_SESSION[\Warehouse\Constants\SessionVariableNames::CONFIG_SITE]) ?
        $_SESSION[\Warehouse\Constants\SessionVariableNames::CONFIG_SITE] : null;

    if (is_null($sessionInConfig)){
      Log::debug("======SESSION --- Configuration was not loaded, asking ConfigurationManager to load a new one");

      ConfigurationManager::LoadStaticConfigurations();
      self::_loadConfigIntoSession(true);
    }
    else{
      Log::debug("=================== it was loaded already in session.");

      return $sessionInConfig;
      //$c = count($sessionInConfig->GetLoadedResourceCount());
    //  var_dump($_SESSION[\Warehouse\Constants\SessionVariableNames::CONFIG_SITE]);
    //  Log::debug("Count was $c");
    }
  }
  /*
  *  Get the count of currently loaded resources
  */
  public static function GetLoadedResourceCount(){

    $configuration = $_SESSION[\Warehouse\Constants\SessionVariableNames::CONFIG_SITE];
    return count($configuration->Resources);
  }
}
