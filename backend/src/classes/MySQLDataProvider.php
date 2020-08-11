<?php

require_once "DataProvider.php";
require_once "Util.php";
/*
MySQLDataProvider
*/
class MySQLDataProvider extends DataProvider {

  // Holds mysqli prepared statement objects
  protected $_preparedStatementObjects;

  function __construct() {
    parent::__construct();
  }
  /*
   Make the DB connection, and set the local property
  */
  protected function _connect(){
    $this->databaseName = DATABASE_MYSQL_NAME;

    $host           = DATABASE_MYSQL_HOST;
    $user           = DATABASE_MYSQL_USER;
    $password       = DATABASE_MYSQL_PASSWORD;
    $port           = DATABASE_MYSQL_PORT;

    $this->_dbConnection = new mysqli($host, $user, $password, $this->databaseName, $port);
    if (mysqli_connect_error()) {
      die('Connect Error (' . mysqli_connect_errno() . ') '
              . mysqli_connect_error());
            }
  }

  /*
    Prepare a single DB statement
    Customization for MySQL:  Store the resulting object in a member array
  */
  protected function prepareSingleStatement($statementName, $statementString)
  {
    $success = false;
    $errorMessage = "ERROR preparing $statementName using $statementString";
    try {

      $preparedStatement = $this->_dbConnection->prepare($statementString);

      if(!empty($preparedStatement))
      {
        $num_statment_params = $preparedStatement->param_count;

        Log::debug("STATEMENT PREPARED $statementName WITH $num_statment_params  parameters");

        $this->_preparedStatementObjects[$statementName] = $preparedStatement;

        $success = true;
      }
      else
      {
          Log::error("$statementName prepare was ERROR - $errorMessage");
      }

    } catch (\Exception $e) {
        Log::error($errorMessage . " " . $e->getMessage());
    }

    return $success;
  }
  /*
   return CSV of prepared statements
  */
  public function ShowPreparedStatements()
  {
    $str = "";
    foreach (array_keys($_preparedStatementObjects) as $statementName) {
       $str .= $statementName . ", ";
    }
    return substr($str, 0, -1);
  }
  public function insertrecord($resourceName, $fieldData){
    return $this->_sqlExecuteStatement(Warehouse\Constants\SqlPrepareTypes::SQL_INSERT . $resourceName, $fieldData );
  }
  /* Update a record */
  public function updaterecord($resourceName, $fieldData){
    return $this->_sqlExecuteStatement(Warehouse\Constants\SqlPrepareTypes::SQL_UPDATE . $resourceName, $fieldData );
  }
  /*
    Gets records using specific DB column as field to check recordIDs against
  */
  public function getRecordsMatchingField($resourceName, $values, $fieldName){
    $fieldData = array_merge([$fieldName], $values);
    return $this->_sqlExecuteStatement(Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD . $resourceName, $fieldData );

  }
  /*  _sqlExecuteStatement - Execute the $sql statement
  @returns records[]
  */
  protected function _sqlExecuteStatement($preparedStatementName, $queryParameters)
  {
    $rows = null;
    $parameterTypes = "";

    try {
      $index=0;
      foreach ($queryParameters as $tokenValue) {
        if ($index>0){
          $parameterTypes .= ",";
        }
        //TODO: this needs to be pulled from config, also we are looking at types
        //   that we should know they are (i.e. in config )
        $dataType = gettype($tokenValue);

        switch($dataType){
          case "string":
            $parameterTypes .= "s";
            break;
          case "integer":
            $parameterTypes .= "i";
            break;
          case "double":
            $parameterTypes .= "d";
            break;
          case "undefined":
            $parameterTypes .= "b";
            break;
        }
      }

      $num_statment_params = $this->_preparedStatementObjects[$preparedStatementName]->param_count;

      $boundDataStr = "[";
      foreach ($queryParameters as $paramValue) {
        $boundDataStr .= $paramValue . ',';
      }

      $boundDataStr = substr($boundDataStr, 0,-1) . "]";

      $preparedString = self::$PreparedStatementStrings[$preparedStatementName];

      Log::debug("BINDING--------- prepared statement $preparedStatementName with $num_statment_params values: $boundDataStr");

      $this->_preparedStatementObjects[$preparedStatementName]->bind_param($parameterTypes, ...$queryParameters);

      Log::debug("EXECUTING--------- the statement $preparedStatementName using string $preparedString");

      mysqli_stmt_execute($this->_preparedStatementObjects[$preparedStatementName]);

      $result = mysqli_stmt_get_result($this->_preparedStatementObjects[$preparedStatementName]);

      if (empty($result))
      {
        $lastErr = $this->_dbConnection->error;
        Log::error("ERROR WITH EXECUTE - SQL statement {$preparedString} USING DATA $boundDataStr  detail: $lastErr");
      }
      else{
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
          $rows[] = $row;
        }
      }

    } catch (\Exception $e) {

        Log::error($e->getMessage());
    }
    return $rows;
  }
  protected function _sqlGetRecordsMatchingFields($preparedStatementName, $fields)
  {


  }
  /*
    Find record using uname and passwd, look at profilename and emailaddress fields
    @returns:  userID of the found resource OR 0 if none found
  */
  protected function _authenticate($resource, $uname, $rawpassword){

    // TODO: Better way to do this bound parameter multiple times
    // passing in uname to support checking emailAddress or profilename using the uname sent in
    $userRecords = $this->_sqlExecuteStatement("AUTH", [$uname,$uname]);

    if ($userRecords==null || count($userRecords) < 1){
      Log::error("No record matching $uname for profilename OR emailaddress. Remember, that these fieldnames can not be changed in config.");
      return 0;
    }
    else{
      return password_verify($rawpassword, $userRecords[0]["upasswd"]) ? $userRecords[0]["id"] : 0;
    }
  }
  /*
    Add a user
    @returns: userid, errMsg or empty
  */
  protected function _adduser(  $resource, $emailaddress, $username,
                                $password_raw, $firstname,
                                $lastname, $city, $state, $zip)
  {
    $errMsg = "";
    $newUserID = 0;//Holds the newly created Userid

    //hash the password before inserting it
    $pwd_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

    $sql = "INSERT INTO User (  `emailaddress`,
                                `profilename`,
                                `upasswd`,
                                `firstname`,
                                `lastname`,
                                `city`,
                                `state`,
                                `zip`) VALUES (?,?,?,?,?,?,?,?)";

    $preparedAddUser = $this->_dbConnection->prepare($sql);

    $userData = array($emailaddress, $username, $pwd_hashed,
                      $firstname, $lastname, $city, $state, $zip);

    //bind
    $preparedAddUser->bind_param("ssssssss", ...$userData);

    try {
      //execute
      $preparedAddUser->execute();
      $result = mysqli_stmt_get_result($preparedAddUser);

      $newUserID = $this->_dbConnection->insert_id;

    }
    catch (\mysqli_sql_exception $e) {
      $errMsg = $this->_dbConnection->error;


    }
    //If already failed due to exception
    if (!empty($errMsg) || empty($result))
    {
      $errMsg = empty($result) ? $errMsg : $this->_dbConnection->error;;
      Log::error("ERROR WITH ADDING NEW USER  detail: $errMsg");
    }


    return array($newUserID, $errMsg);
  }
  /*
     - Set an Authorization Code
     - Add an entry into the authorization table
     - IF userID exist: this is used as part of authorization flow during site sessioning
     - If userID NOT Exist, an entry still needs to be set:
              (to ensure our software is doing these things and not a bot)
     -     initial login (step 1 - set by Auth API and checked later when issuing AccessToken )
     -     password-reset flow (step 1 - request a reset link)

    @returns authCode or null
  */
  protected function _setAuthorizationCode($userID=0, $expirySeconds){

    Log::error("Generating an AUTH code");
    $authCode = Util::GenerateAuthorizationCode();
    $userID = empty($userID) ? 0 : $userID;

    $userIDMsg = '';
    $msg = "AUTH CODE ";

    $expiresUnixTimestamp = time() + $expirySeconds;

    // TODO: databse default is already set to 0 for userid , this can be removed
    $sql = "INSERT INTO `authorization` (`userid`, `authcode`, `expires_unix_time`)
                            VALUES ($userID, '$authCode', $expiresUnixTimestamp)";

    $this->_dbConnection->query($sql);
    if ($this->_dbConnection->affected_rows>0){

      $msg .= " SET SUCCESS - $authCode";

      //Log::info($msg);
      $t = time();
      $edate = Util::GetFormattedDate($expiresUnixTimestamp);
      $nowDate = Util::GetFormattedDate($t);


      Log::error("GENERATED $authCode will expire at $edate it is $nowDate");

      return $authCode;
    }
    else{
      $msg .= " SET FAILURE ";

      Log::error($msg);
      return null;
    }
  }
  /*
    Get an AccessToken using a valid AuthCode and userID combo
    Validate there is a record in authorization with an AuthCode
    return $accessToken or null ;
  */
  protected function _setAccessToken($userID, $authCode, $expirySeconds){

    Log::error("Generating an Access Token");
    $returnData = array();

    $error = true;
    $accessToken = null;

    $expiresUnixTimestamp = time() + $expirySeconds;

    //Verify the data (instead of sql-injection)
    if (  is_nan($userID) ||
          strpos($authCode, " ") ||
          strlen($authCode)!=36
        )
    {
      Log::error("BAD USER_ID $userID INPUT FOR VALIDATE AUTH CODE $authCode");
    }
    else {
      //Generate an Access Token;
      $accessToken = UTIL::GenerateAccessToken();

      Log::debug("SWAPPING AuthCode FOR AccessToken for user $userID .  Access Token - $accessToken");

      $sql = "UPDATE `authorization` SET
                `accesstoken`='$accessToken',
                `expires_unix_time`=$expiresUnixTimestamp,
                `authcode` = NULL
                WHERE userid=$userID AND `authcode`='$authCode'";


      if ($this->_dbConnection->query($sql) === TRUE){
        $returnData["accessToken"]        = $accessToken;
        $returnData["expires_unix_time"]  = $expiresUnixTimestamp;

        $edate = Util::GetFormattedDate($expiresUnixTimestamp);
        Log::debug("AccessToken/authCode updated successfuly in DB for $userID.  It will expire at $edate");
        $error = false;
      }
      else{
        Log::error("Error with the update statement while swapping authCode for accessToken");
      }
    }
    return $returnData;
  }
  /*
    Validate an entry exist matching userID and accessToken
    @param: $currentAccessToken
    @param: $extendSeconds - add seconds to current expiration
    @returns: array [accessToken][expires_unix_time]
  */
  protected function _validateAccessToken($currentAccessToken, $userID, $extendSeconds=0)
  {

    $returnData = array();

    $sql = "SELECT `expires_unix_time` FROM `authorization` WHERE `accesstoken` = '$currentAccessToken' AND `userid`=$userID";


    Log::debug($sql);
    $result = $this->_dbConnection->query($sql);
    if ($result->num_rows > 0){

      $row        = $result->fetch_assoc();
      $expiryTime = $row["expires_unix_time"];

      $timeNow = time();

      // Token IS VALID
      if ($expiryTime>$timeNow)
      {
        //Default return data to current record
        $returnData["accessToken"]        = $currentAccessToken;
        $returnData["expires_unix_time"]  = $expiryTime;

        //SHOULD IT BE EXTENDED ?
        if ($extendSeconds>0){

          $newAccessToken = UTIL::GenerateAccessToken();
          Log::info("Access token has been requested to be extended.  Generating a new one - $newAccessToken");

          $newExpiryUnixTimestamp = time() + $extendSeconds;

          $sql = "UPDATE `authorization` SET
                    `accesstoken`='$newAccessToken',
                    `expires_unix_time`=$newExpiryUnixTimestamp,
                    `authcode` = NULL
                    WHERE userid=$userID AND `accesstoken`='$currentAccessToken'";


          if ($this->_dbConnection->query($sql) === TRUE){
            $returnData["accessToken"]        = $newAccessToken;
            $returnData["expires_unix_time"]  = $newExpiryUnixTimestamp;

            $edate = Util::GetFormattedDate($newExpiryUnixTimestamp);
            Log::debug("AccessToken/authCode updated successfuly in DB for $userID.  It will expire at $edate");
            $error = false;
          }
          else{
            Log::error("Error with the update statement while swapping authCode for accessToken");
          }
        }
      }
      else{ // TOKEN IS EXPIRED

        $expiredTime = Util::GetFormattedDate($expiryTime);
        Log::info("ACCESS TOKEN IS EXPIRED '$currentAccessToken' at '$expiredTime'.
          Removing this from DB along with other entries if a userID is known");

        $this->_deleteAuthorizations($userID);
      }
    }
    else{
      Log::error("User $userID and accessToken $currentAccessToken NOT FOUND. Cleaning up all existing authorizations for $userID");
      $this->_deleteAuthorizations($userID);
    }

    return $returnData;

  }
  /*
  * Get access Token data
  * returns: array[userID, expires_unix_time]
  */
  protected function _getAccessTokenData($accessToken){

    $returnData = array();
    $sql = "SELECT `userid`, `expires_unix_time` FROM `authorization` WHERE `accesstoken`='$accessToken'";

    $result = $this->_dbConnection->query($sql);
    if ($result->num_rows > 0){
      $row = $result->fetch_assoc();
      $returnData["userID"] = $row["userid"];
      $returnData["expires_unix_time"] = $row["expires_unix_time"];
    }
    return $returnData;
  }
 /*
    Verify that a user exists
    @param: $emailaddress - REQUIRED
    @param: $userID - optional
    @param: $authCode - optional

    @returns: $userID
  */
  protected function _verify_user_exists($emailaddress, $profileName, $authCode){

    $userID = 0;

    $sql = "SELECT user.id FROM user";

    if (!empty($authCode)){
      $sql .= ",authorization WHERE authorization.authcode='$authCode' AND ";
    }
    else{
      $sql .= " WHERE";
    }
    $sql .= " user.emailaddress='$emailaddress'";

    if (!empty($profileName)){
      $sql .= " AND user.profilename='$profileName'";
    }

    $result = $this->_dbConnection->query($sql);
    if ($result->num_rows>0){
      $row = $result->fetch_row();
      $userID = $row[0];

      if ($userID>0)
      {
        //Now update the authToken table to include this matched userID

        $sqlUpdate = "UPDATE `authorization` SET `userid`=$userID WHERE `authcode`='$authCode'";

        if ($this->_dbConnection->query($sqlUpdate) === TRUE){
          Log::debug("AuthCode updated successfuly to include userID=$userID");
        }
        else{
          Log::error("ERROR while trying to update $userID to the authorization table.  SQL:  $sqlUpdate");
        }
      }
      else{
        Log::error("ERROR userid was not returned using $emailaddress and $authCode");
      }

    }

    return $userID;

  }
  /*
    Verify an authToken has not expired in the authtokens table
    return $userID, 0 if empty or -1 if expired
  */
  protected function _validateAuthCode($authCode){

    $userID = -1;
    $sql = "SELECT `userid`, `expires_unix_time` FROM `authorization` WHERE `authcode`='$authCode'";
    $result = $this->_dbConnection->query($sql);

    if ($result->num_rows > 0){

      $row        = $result->fetch_assoc();
      $userID     = $row["userid"];
      $expiryTime = $row["expires_unix_time"];

      $timeNow = time();

      if ($expiryTime>$timeNow){
        Log::error('all good');
        return $userID;
      }
      else{
        Log::error("$expiryTime > $timeNow .. DELETING THIS AUTH");

        $expiredTime = Util::GetFormattedDate($expiryTime);
        Log::error("AUTHCODE EXPIRED '$authCode' at '$expiredTime'.
          Removing this from DB along with other entries if a userID is known");

        $sqlDelete = "DELETE FROM `authorization` WHERE `authcode`='$authCode'";
        if (!empty($userID)){
          $sqlDelete .= " OR `userid`=$userID";
        }

        Log::error("DELETING OLD AUTH: " . $sqlDelete);

        if ($this->_dbConnection->query($sqlDelete) === TRUE){
          $userID = empty($userID) ? 0 : $userID;//????
          Log::info("DELETE authCodes Success for userID=$userID");
        }
        else{
          Log::error("ERROR while trying to remove old authCodes -  $sqlDelete");
        }
      }
    }
    return $userID;
  }
  /*
    Reset a user's password
    @param - $userID of the requested user
    @param - $password_raw - the raw text password
  */
  protected function _resetPassword($userID, $password_raw){

    $error = true;
    $newPasswordHash = password_hash($password_raw, PASSWORD_DEFAULT);
    $sqlUpdate = "UPDATE `user` SET `upasswd`='$newPasswordHash' WHERE `id`=$userID";
    Log::debug($sqlUpdate);
    if ($this->_dbConnection->query($sqlUpdate) === TRUE){
      Log::debug("PASSWORD RESET SUCCESS for userID=$userID");
      $error = false;
    }
    else{
      Log::error("ERROR while trying to update password for user $userID .  SQL:  $sqlUpdate");
    }

    return $error;
  }
  /*
    Determine if a $userID is in adminusers table
    @param: $userID
    @returns: bool
  */
  /*
    @param: userID
    @returns: Success bool
  */
  protected function _deleteAuthorizations($userID = 0){

    Log::error("delete authorizations");
    $userID = is_nan($userID) ? 0 : $userID;

    $currentTime = time();

    $sqlDelete = "DELETE FROM `authorization` WHERE";

    if ($userID>0){

      $sqlDelete .= " `userid`=$userID";
    }
    else{
      $sqlDelete .= " `expires_unix_time`< $currentTime";
    }

    Log::debug("CLEANUP AUTHS for User - $sqlDelete");

    $this->_dbConnection->query($sqlDelete);
    $deletedRows = $this->_dbConnection->affected_rows;

    if ($deletedRows>0){
      Log::info("DELETE authorizations Success for userID=$userID");
    }
    else{
      Log::warning("Nothing to delete -  $sqlDelete");
    }
  }
  /*
    @returns: bool
  */
  protected function _isUserAdmin($userID){

    if ($userID==0 || is_nan($userID))
    {
      Log::error("IsUserAdmin - BAD REQUEST for $userID");
      return false;
    }
    else{
      $sql = "SELECT `userid` FROM `adminusers` WHERE `userid`=$userID";
      $result = $this->_dbConnection->query($sql);
      return ($result->num_rows>0);
    }
  }
  /*
  *  Return true based on count of results > 0
  */
  protected function RecordsExist($sql){
    Log::info("CALLING RecordsExisting using SQL $sql");
    $result = $this->_dbConnection->query($sql);
    return ($result->num_rows > 0);
  }
  /*
  *  Get all records owned by user
  *   returns mysql row result format, rows[] = {'name', 'id'}
  */
  protected function _getOwnedRecordsForResource( $resourceTableName,
                                                  $ownerFieldName,
                                                  $displayFieldName,
                                                  $userID){

    $recordsForResource = array();
    $sql = "SELECT `id`, `$displayFieldName` FROM `$resourceTableName` WHERE `$ownerFieldName` = $userID";

    $result = $this->_dbConnection->query($sql);
    $recordsForResource = (!empty($result)) ?
                                $result->fetch_all(MYSQLI_ASSOC) : null;


    $c = count($recordsForResource);
    Log::debug("OWNER ========= FOUND $c records owned by $userID using SQL $sql");


    return $recordsForResource;
  }
  /*
  *  Get all records associated to user
  *   returns int[] or empty []
  */
  protected function _getAssociatedRecordsForResource(  $associationTablename,
                                                        $ownerFieldName,
                                                        $fieldSQLForDisplay,
                                                        $userID){

    //SELECT storageitem.name from storageitem
    //  JOIN storagecontainerinventory ON storagecontainerinventory.containerid=storageitem.id
    //     WHERE storageitem.ownerid=361

    $recordsForResource = array();
    $sql = "SELECT `id`, `$displayFieldName` FROM `$associationTablename` WHERE `$ownerFieldName` = $userID";

    $result = $this->_dbConnection->query($sql);
    while ($recordID= $result->fetch_row()){
      $recordsForResource[] = $recordID;
    }

    $c = count($recordsForResource);
    Log::debug("OWNER ========= FOUND $c records owned by $userID using SQL $sql");

    return $recordsForResource;
  }
}
