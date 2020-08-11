<?php
/*
  Authentication/Authorization Service

  - Validate a username/password
  - Verify an authCode, Transient authCode, or AccessToken

*/

/**
 *
 */
class SecurityManager
{

  //These are the currently inbound Tokens and codes ($GET or $POST)
  public static $AuthCode;
  public static $AccessToken;

  public static $AccessTokenExpiry;

  public static function SetTokens()
  {
    //Grab some auth items if they exist
    $accessToken  = isset($_GET['accessToken'])   ? $_GET['accessToken']   : null;
    $authCode     = isset($_GET['authCode'])      ? $_GET['authCode']      : null;

    //Look in post ONLY if was not in GET
    if (empty($authCode)){
      $authCode     = isset($_POST['authCode'])    ? $_POST['authCode']     : null;
    }
    if (empty($accessToken)){
      $accessToken  = isset($_POST['accessToken']) ? $_POST['accessToken']  : null;
    }

    if (!is_null($authCode)){
      self::SetAuthCode($authCode);
    }
    if (!is_null($accessToken)){
      self::SetAccessToken($accessToken);
    }



    return true;
  }

  /*
  * Verify a username/password match in DB correctly
  *
  */
  public static function ValidateUser($username, $passwordRaw)
  {

  }
  /*
    Validate an AuthCode
    @param $authCode
    @param $userID Optional - Enforced if present
  */
  public static function ValidateAuthCode($authCode, $userID=null)
  {
    return DataProvider::AUTH_VALIDATE($authCode, $userID);

  }
  /*
  * Validate an AccessToken
  * @returns: array [accessToken][expires_unix_time]
  */
  public static function ValidateAccessToken($accessToken,$userID, $extendSeconds=0)
  {

    return DataProvider::ACCESS_TOKEN_VALIDATE($accessToken,$userID, $extendSeconds);


  }
  public static function SetAuthCode($authCode){
    self::$AuthCode = $authCode;
  }
  public static function SetAccessToken($accessToken){
    self::$AccessToken = $accessToken;
  }
}
