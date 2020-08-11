<?php

/*
Class DataProvider provides common CRUD operations to multiple DB types (mysql/pgsql)
 used for  managing data in a DB (mysql or postgresql)
*/
class DataProvider {

  private $_dbConnection;

  function __construct() {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    //todo - don't use this, try to enforce singleton
    //Call the connection
    $this->_connect();

  }

  // STATIC MEMBERS

  // Singleton instance of the actual data provider
  private static $_dbProviderInstance;

  // Dictionary of Parametized strings used for DB prepare statements, keyed
  //  by Constants
  public static $PreparedStatementStrings;



  /*
    Get Singleton instance of the current DataProvider
  */
  public static function GetInstance(){

    if (empty(self::$_dbProviderInstance)){
      Log::debug("Initializing DataProvider instance of type: " . DATABASE_TYPE);

      try {
        if (DATABASE_TYPE==DATABASE_MYSQL){
            self::$_dbProviderInstance = new MySQLDataProvider();

        }
        else if (DATABASE_TYPE==DATABASE_POSTGRESQL){
            self::$_dbProviderInstance = new PostgreSQLDataProvider();
        }

      } catch (Exception $e) {
        Log::error($e->getMessage());
      }
    }
    return self::$_dbProviderInstance;
  }
  /*
    Add common prepared statements for a specific resource
    Should be called when a resource is loaded for the first time
  */
  public static function AddCommonPreparedStatementStrings($resource){

    //TODO - Refactor :
    //  This is called each time a resource is constructed,
    //    however queries are mostly same a class level
    //    Need to classify DB query types and call as lazy loading

    $tableName = $resource->tableName;


    $indexFieldName         = $resource->_indexFieldName;
    $dbLabels               = $resource->DB_Labels;
    //Linked collections with a foreign key pointing to this resource
    $dependentCollections   = $resource->DependentResources;
    //Linked collections using an associative table
    $associativeCollections = $resource->Associations;

    // Common prepare statements for all resources
    $databaseName = DATABASE_TYPE  === DATABASE_MYSQL ? DATABASE_MYSQL_NAME : DATABASE_PG_NAME;
    $full_tableName = $databaseName . "." . $tableName;

    //Common SQL Templates
    $whereBase  = " WHERE " . $indexFieldName;
    $selectBase = "SELECT * FROM "  . $full_tableName;
    $deleteBase = "DELETE FROM "    . $full_tableName;
    $insertBase = "INSERT INTO "    . $full_tableName;
    $updateBase = "UPDATE "         . $full_tableName;


    //Token idenfifiers.  Mysql uses ?, Postgrest uses $n;where n>1 and n<=columns

    $token       = DATABASE_TYPE === DATABASE_MYSQL ? "?" : "$1";

    // -- BUILD Token list, it is dynamic in size
    //Token array is used for INSERT and UPDATES
    // First create an array of tokens, then format it csv
    $tokenArray = array_fill(0, count($dbLabels), $token[0]);

    $index = 1;
    $tokenValues = [];
    //PostgreSQL uses $1, $2 instead of just $
    if (DATABASE_TYPE===DATABASE_POSTGRESQL){
        foreach ($tokenArray as $tokenValue) {
          $tokenValues[] = $tokenValue . $index++;
        }

    }
    else{
      $tokenValues = $tokenArray;
    }
    $parametercount = count($tokenValues)+1;

    //Now add in the column names as keys and tokens as values
    //  used for INSERTS and UPDATES
    $tokens = array_combine(array_keys($dbLabels), $tokenValues);// [fieldname]=?

    // INSERT uses a CSV of values matching order of column names:
    //  INSERT INTO table (col1, col2) VALUES (?,?)
    $insertTokencsv = Util::Array2csv($tokens, $tokens, false);

    // UPDATE has different format:
    // UPDATE table SET (col1=$1, col2=$2)

    //requires one additional token for the WHERE clause

    $tokensValues[] = $token; // Now add one more for the indexField
    $tokenIDParam = $token;
    if (DATABASE_TYPE===DATABASE_POSTGRESQL){

      $tokenIDParam = $token[0] . $parametercount;
      $tokenValues[] = $tokenIDParam;
    }

    $updateSetClause = "";
    $i=0;
    foreach (array_keys($dbLabels) as $fieldName) {
      $updateSetClause .= $fieldName . " = " . $tokenValues[$i++];
      if ($i < $parametercount-1){
        $updateSetClause .= ",";
      }
    }

    // Foreign Linked fields  - WHERE foreignTable.foreignFieldName = ID



    //Log::debug("DataProvider::AddCommonPreparedStatementStrings ($tableName) - Setting up the string templates for resource $tableName");

    //Only build strings for prepared resources not already loaded
    if (  !empty(self::$PreparedStatementStrings) &&
      (
        (array_key_exists(Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_IN . $tableName, self::$PreparedStatementStrings))
        || (array_key_exists(Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD . $tableName, self::$PreparedStatementStrings))
        || (array_key_exists(Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_GREATER . $tableName, self::$PreparedStatementStrings))
        || (array_key_exists(Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_LESS . $tableName, self::$PreparedStatementStrings))
        || (array_key_exists(Warehouse\Constants\SqlPrepareTypes::SQL_DELETE . $tableName, self::$PreparedStatementStrings))
        || (array_key_exists(Warehouse\Constants\SqlPrepareTypes::SQL_UPDATE . $tableName, self::$PreparedStatementStrings))
        || (array_key_exists(Warehouse\Constants\SqlPrepareTypes::SQL_INSERT . $tableName, self::$PreparedStatementStrings))
        )
    )
    {
      Log::warning("Duplicate strings already defined for PREPARE on $full_tableName.  LOGIC ERROR");
    }
    else{

      self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_IN . $tableName]       =
            $selectBase . $whereBase . " IN (" . $token . ")";

      self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_GREATER . $tableName]  =
            $selectBase . $whereBase . " > " . $token;
      self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_LESS . $tableName]     =
            $selectBase . $whereBase . " < " . $token;
      self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_DELETE . $tableName]          =
            $deleteBase . $whereBase . " = " . $token;
      self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_INSERT . $tableName]          =
            $insertBase . "(" . Util::Array2csv(array_keys($dbLabels), $dbLabels) .
                          ") VALUES (" . $insertTokencsv. ")";
      self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_UPDATE . $tableName]          =
            "$updateBase SET $updateSetClause WHERE $indexFieldName = $tokenIDParam";
      //$tokenIDParam will be ? for mysql OR $# for PostgreSQL where # is sequenced parameter

      //THERE WILL BE ONE QUERY PER UNIQUE Linked Collection
      //   THIS can be used to find fields in one table that hold value to another
      //   storageItems.ownerID = 1, etc ..

      if (!empty($dependentCollections)){

        foreach ($dependentCollections as $collectionName => $linkedCollectionItem) {
          $linkedTablename = $linkedCollectionItem["LinkedResourceName"]::$Tablename;
          $statement = "SELECT * FROM " . $linkedTablename  . " WHERE " . $linkedCollectionItem["LinkedFieldName"] . " IN ($tokenValues[1])";
          self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD . $tableName . $collectionName] = $statement;
        }

      }
      // -------------------------------------------
      // ASSOCIATIVE Lookup Tables
      //THERE WILL BE ONE QUERY PER UNIQUE Associative Collection
      if (!empty($associativeCollections)){

        foreach ($associativeCollections as $collectionName => $associativeCollectionItem) {

          $linkedTablename = '';
          $associativeKeyField = $associativeCollectionItem["associativeKeyField"];
          $associateKeyFieldItems = explode(".", $associativeKeyField );
          $associativeTablename = $associateKeyFieldItems[0];
          $associativeTablePrimaryFieldName = $associateKeyFieldItems[1];

          $associationObjects = $associativeCollectionItem["associationObjects"];

          foreach (array_keys($associationObjects) as $foreignResourceName) {

            $associationObject                = $associationObjects[$foreignResourceName];
            $linkedKeyField                   = $associationObject["LinkedFieldName"];
            $linkedKeyFieldItems              = explode(".", $linkedKeyField);
            $associatedTableForeignFieldName  = $linkedKeyFieldItems[1];
            $foreignResourceTablename         = $foreignResourceName::$Tablename;
            $foreignResourceIndexFieldname    = $foreignResourceName::$IndexFieldname;

            // -----------------------
            //NEED A SELECT, INSERT, AND DELETE for ASSOCIATIVE  TABLES

            $statement = "SELECT * FROM $foreignResourceTablename"  .
                        " JOIN " . $associativeTablename . " ON " .
                        $linkedKeyField . " =  $foreignResourceTablename.$foreignResourceIndexFieldname WHERE $associativeKeyField IN ($tokenValues[1])";
            self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD . "$tableName.$collectionName.$foreignResourceName"] = $statement;

            $statement = "INSERT INTO $associativeTablename ($associativeTablePrimaryFieldName, $associatedTableForeignFieldName) VALUES (?,?)";

            self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_INSERT . "$tableName.$collectionName.$foreignResourceName"] = $statement;

            $statement = "DELETE FROM $databaseName.$associativeTablename WHERE $associativeTablePrimaryFieldName=? AND $associatedTableForeignFieldName=?";

            self::$PreparedStatementStrings[Warehouse\Constants\SqlPrepareTypes::SQL_DELETE . "$tableName.$collectionName.$foreignResourceName"] = $statement;
          }
        }
      }

      // TODO: make this work for postgres, ensure tokens[] is not bound to field count

      //Add the Authentication prepared statements
      // NOTE: requires tablename 'user'
      self::$PreparedStatementStrings["AUTH"] = "SELECT * FROM user WHERE profilename = ? OR emailaddress = ?";

      if (!self::GetInstance()->prepareCommonStatements()){
        die("Error with prepared statements for $tableName");
      }
      else
        Log::debug("Statements prepared successfully for $tableName");
      }

  }
  /*
    Prepare all common statements in DB, return immediately on failure
  */
  protected function prepareCommonStatements()
  {
    foreach (DataProvider::$PreparedStatementStrings as $statementName => $statementString) {
      //Now add these prepared statements to the DB INSTANCE
      Log::debug("DataProvider::prepareCommonStatements: Preparing sql $statementName = $statementString");

      if (!self::GetInstance()->prepareSingleStatement($statementName, $statementString))
        return false;
    }
    return true;
  }
  /*
  *   Get a collection of records that have the dependency field matching this resource's id
  *
  *   There is already a prepared statement setup ready to accept the primaryID
  *      $resource->$tableName.$dependentResourceName = (select foreign where fieldname=primaryid)
  *
  *   @param - resource - the resource object with place-holders loaded for the dependency items

  *   @returns - array[$resourceName] = resources[ ]
  *
  */
  public static function GetDependentRecords($resource){
    $returnedDependencies = [];

    foreach ($resource->DependentResources as $dependencyName => $dependentResourceItem) {

          $preparedStatementValues  = array(intval($resource->ID));
          $preparedStatementName    =
                Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD .
                $resource->tableName .
                $dependencyName;

          $foreignResourceName  = $dependentResourceItem["LinkedResourceName"];

          $dependentResourceObjects = $foreignResourceName::GetInstancesUsingQuery(
                                                      $preparedStatementName,
                                                      $preparedStatementValues);

          if (!empty($dependentResourceObjects)){
            $num = count($dependentResourceObjects);
            Log::debug("Get Dependencies - DataProvider - Found $num $foreignResourceName items that depend on $resource->Name");
            $returnedDependencies[$dependencyName] = $dependentResourceObjects;
          }
          else{
            Log::debug("Get Dependencies - DataProvider - No dependent $dependencyName resources found for RESOURCE $resource->Name.
                        It will not be added to the collection of returned items.");
          }
      }
      return $returnedDependencies;
  }
  /*
    Get collection of associated resources
    @param: $resource - Resource (User, StorageFacility)
    @returns: array[associativecollectionName]["ForeignResources"]["associativeTablename"]
                                              ["associativeTablePrimaryFieldName"]
        [associationObjects] = {'displayConfigParams', Records[]}
  */
  public static function GetAssociatedRecords($resource){

    $returnedAssociations = [];
    foreach ($resource->Associations as $associativeCollectionName=>$associativeCollectionItem)
    {
        $preparedStatementValues = array(intval($resource->ID));

        //Pull out the keyfield, will need it to build the disassociate url
        $associativeKeyField              = $associativeCollectionItem["associativeKeyField"];
        $associateKeyFieldItems           = explode(".", $associativeKeyField );
        $associativeTablename             = $associateKeyFieldItems[0];
        $associativeTablePrimaryFieldName = $associateKeyFieldItems[1];

        $returnedAssociations[$associativeCollectionName]["associativeTablename"]             = $associativeTablename;
        $returnedAssociations[$associativeCollectionName]["associativeTablePrimaryFieldName"] = $associativeTablePrimaryFieldName;

        $associationObjects = $associativeCollectionItem["associationObjects"];

        $foreignResources = [];
        foreach (array_keys($associationObjects) as $associationObjectKey) {

          $associationObject    = $associationObjects[$associationObjectKey];
          $foreignResourceName  = $associationObjectKey;
          $linkedItems          = explode(".",$associationObject["LinkedFieldName"]);
          $linkedfieldName      = $linkedItems[1];
          $foreignResource      = new $foreignResourceName();
          $listSize             = $associationObject["ListSize"];
          $foreignResourceLabel = $associationObject["displayText"];

          $preparedStatementName = Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD .  $resource::$Tablename . ".$associativeCollectionName.$foreignResourceName";

          $linkedObjects = $foreignResource::GetInstancesUsingQuery($preparedStatementName, $preparedStatementValues);

          $foreignResources[$associationObjectKey] =
                  array('ForeignResourceLabel'  => $foreignResourceLabel,
                        'LinkedFieldName'       => $linkedfieldName,
                        'ListSize'              => $listSize,
                        'LinkedResources'         => $linkedObjects
                        );
        }
        Log::debug("DataProvider - Adding data for  $associativeCollectionName");


        $returnedAssociations[$associativeCollectionName]["ForeignResources"] = $foreignResources;
    }

    return $returnedAssociations;

  }
  /*
    Set User hashed password into DB
  */
  public static function ChangePassword($resource, $password_raw){

    //hash the password
    $hashed_passwd = password_hash($password_raw);

    $sql = "UPDATE $resource->$Tablename SET ('upasswd') VALUES (?) WHERE id=" . $resource->ID;

  }
  /*
    Determine if a $userID is in adminusers table
    @param: $userID
    @returns: bool
  */
  public static function IsUserAdmin($userID){
    return self::GetInstance()->_isUserAdmin($userID);
  }
  /*
  Add a new user
  @returns:  array(newUserID/0; errMsg/empty)
  */
  public static function ADD_USER($emailaddress, $username, $password_raw, $firstname,
                                  $lastname, $city, $state, $zip)
  {

    $resource = new User();//This is only needed to get a flexible $Tablename
    return self::GetInstance()->_adduser( $resource, $emailaddress, $username,
                                          $password_raw, $firstname,
                                          $lastname, $city, $state, $zip);


  }
  /*
    Verify hashed password is correct
    // NOTE: THIS DOES NOT USE standard prepare statements, it is all done in this function
    //  Resource is not passed in because it is always a new User which is created to verify existance and password
    @returns: BOOL
  */
  public static function AUTHENTICATE($uname, $rawpassword){

    $resource = new User();//This is only needed to allow for a flexible $Tablename in the config

    return self::GetInstance()->_authenticate($resource, $uname, $rawpassword);
  }
  /*
    Authenticate a user and set a temporary authCode in DB
     - LOGIN STEP 1 - This is the first part of a login flow
                        towards grant of an access token
    @returns: array[userid,authCode] or NULL
  */
  public static function LOGIN($uname, $rawpassword){

    $userID = DataProvider::AUTHENTICATE($uname, $rawpassword);

    if ($userID){
      $authCode = self::GetInstance()->_setAuthorizationCode($userID, TOKEN_TTL_SESSION_AUTOLOGOUT);

      if (!is_null($authCode)){
        return array($userID, $authCode);
      }
    }
    return null;
  }
  /*
    Generate a short-lived auth-code, insert into DB 'authcodes' with short expiry
    @param: $expiryInSeconds - Number of seconds before expiring this auth-code
  */
  public static function GET_AUTH_CODE($expiryInSeconds){

    return self::GetInstance()->_setAuthorizationCode(null, $expiryInSeconds);

  }
  /*

    Get a Security Access Token using a recently generated AuthCode
    It will exist in DB.  After verification, remove authCode from DB
     - LOGIN STEP 2 -- This is normally second part of a login flow that is detected by
        the router for /Login/
    @param: $userID - Required userID
    @param: $authCode - The AuthCode
    @param: $expiryInSeconds - Number of seconds from now to expire this access token
    @return: array[$accessToken, $expires_unix_time]
  */
  public static function SET_ACCESS_TOKEN($userID, $authCode, $expiryInSeconds){

    return self::GetInstance()->_setAccessToken($userID, $authCode, $expiryInSeconds);
  }
  /*
    Verify that a user exists
    @param: $emailAddress - REQUIRED
    @param: $profileName - optional
    @param: $authCode - optional

    @returns: $userID
  */
  public static function VERIFY_USER_EXISTS($emailAddress, $profileName=null, $authCode=null ){
        return self::GetInstance()->_verify_user_exists($emailAddress, $profileName, $authCode);
  }
  /*
    Using only an authCode that should exist in DB, reset a user password

    @param: authCode - authorizationCode previously granted
    @param: password_raw - entered on form
    @returns: success or failure
  */
  public static function RESET_PASSWORD($userID, $accessToken, $password_raw){

    $error = true;
    $accessGranted = self::GetInstance()->_validateAccessToken($accessToken, $userID);

    if ($accessGranted){
      $error = self::GetInstance()->_resetPassword($userID, $password_raw);
    }
    else{
      Log::error("SECURITY - Access denied for '$userID' and token '$accessToken' ");
    }

    return $error;
  }
  /*
  * Remove all authorizations for userID
  * @param: $userID defaults to 0
  */
  public static function DELETE_AUTHORIZATIONS($userID=0){

    if (!self::GetInstance()->_deleteAuthorizations($userID)){
      Log::error("ERROR while deleting authorizations for user $userID");
    }
  }
  /*
    Return true/false if authCode is not expired

    @param: $authCode
    @param: $userIDRequired - optional,
                must match return value from DB matching this authcode
  */
  public static function AUTH_VALIDATE($authCode, $userID=null){

    $authorizedUserID = self::GetInstance()->_validateAuthCode($authCode);

    //SUCCESS IF:
    //   NOT EXPIRED AND userID matches if it was requested
    return $authorizedUserID >= 0 &&
           (
             is_null($userID) ||
             (!is_null($userID) && ($authorizedUserID===$userID))
           );
  }
  /*
    Return true/false if accessToken is valid
    @param: $renew - if true, extend the expiry time
    @returns: array [accessToken][expires_unix_time]
  */
  public static function ACCESS_TOKEN_VALIDATE($accessToken, $userID, $extendSeconds=0){
    return self::GetInstance()->_validateAccessToken($accessToken, $userID, $extendSeconds);
  }
  /*
  * Given an accessToken, get additional data:
  * returns: array[userID, expires_unix_time]
  */
  public static function GET_ACCESS_TOKEN_DATA($accessToken){
    return is_null($accessToken) ? null :
                      self::GetInstance()->_getAccessTokenData($accessToken);
  }
  /*
    ASSOCIATE two records into an associate table
      (example:  storagefacilityowners:{faciltyid, userid})

    @param: $resource - The record that has the association
    @param: $associativeCollectionName - string name of the collection
    @param: $fieldData - Data used in the association such as the fieldName and of the other object
              (example.  This resource is storagefacility, so we have that ID.
                          The fieldData would then have the id of the user
                          as well as the name in the associated table )
  */
  public static function ASSOCIATE($resource, $associativeCollectionName, $foreignResourceName, $fieldData)
  {
    $preparedStatementName =  Warehouse\Constants\SqlPrepareTypes::SQL_INSERT .
                              $resource::$Tablename .
                              ".$associativeCollectionName.$foreignResourceName";

    //Now add the data that will be passed into the prepared statement
    /*
      { Resource.ID, ForeignResource.ID }
    */
    $statementData = array($resource->ID);
    $statementData[] = strval(array_values($fieldData)[0]);

    return self::GetInstance()->_sqlExecuteStatement($preparedStatementName, $statementData);

  }

  /*
    DISASSOCIATE two records into an associate table
      (example:  storagefacilityowners:{faciltyid, userid})

    @param: $resource - The record that has the association
    @param: $associativeCollectionName - string name of the collection
    @param: $foreignResourceName - classname of the other resource (i.e. user in facilityowners)
    @param: $fieldData - Data used in the association such as the fieldName and of the other object
              (example.  This resource is storagefacility, so we have that ID.
                          The fieldData would then have the id of the user
                          as well as the name in the associated table )
  */
  public static function DISASSOCIATE($resource, $associativeCollectionName, $foreignResourceName, $fieldData)
  {
    $preparedStatementName =  Warehouse\Constants\SqlPrepareTypes::SQL_DELETE .
                              $resource::$Tablename .
                              ".$associativeCollectionName.$foreignResourceName";

    //Now add the data that will be passed into the prepared statement
    /*
      { Resource.ID, ForeignResource.ID }
    */
    $statementData = array($resource->ID);
    $statementData[] = strval(array_values($fieldData)[0]);

    return self::GetInstance()->_sqlExecuteStatement($preparedStatementName, $statementData);

  }
  public static function AddAssociationRequest(  $userID,
                                        $primaryType,
                                        $primaryRecordID,
                                        $foreignType,
                                        $foreignRecordID)
  {

    return 1;

  }
  /*
  Get records
  @param $recordIDs
  @param $recordIDs
  @returns associate array $row results
  */
  public static function LOAD($preparedStatementName, $dbLabels, $recordIDs){
    Log::debug("Loading records for " . DATABASE_TYPE);
    return self::GetInstance()->loadRecords($preparedStatementName, $dbLabels, $recordIDs);
  }
  public static function INSERT($resource, $fieldData){
    return self::GetInstance()->insertrecord($resource, $fieldData);
  }
  public static function UPDATE($resource, $fieldData){
    return self::GetInstance()->updaterecord($resource, $fieldData);
  }
  public static function DELETE($resource, $fieldData){
    return self::GetInstance()->deleterecord($resource, $fieldData);
  }
  public static function GET($preparedStatementName, $orderedStatementParameters){

    return self::GetInstance()->_sql_GetRecords($preparedStatementName, $orderedStatementParameters);
/*
    echo $preparedStatementName;
    switch($preparedStatementName){

      case Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_IN:
      case Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_GREATER:
      case Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_LESS:
          return self::GetInstance()->_sql_GetRecords($preparedStatementName, $orderedStatementParameters);
          break;
      case Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_WHERE_FIELD:
          return self::GetInstance()->_sqlGetRecordsMatchingFields($resource, $orderedStatementParameters);
          break;
      default:
          echo "here";
          Log::error("Unknown prepared statement $preparedStatementName");
          break;

    }*/

  }
  /*
  delete an existing record
  */
  public function deleterecord($resource, $Id){
    return self::GetInstance()->_sqlExecuteStatement(Warehouse\Constants\SqlPrepareTypes::SQL_DELETE . $resource, [$Id]);
  }
  /*
  Get records, only return one
  */
  public function loadRecords($preparedStatementName, $dbLabels, $recordIDs)
  {

    $result = self::GetInstance()->_sql_GetRecord($preparedStatementName, $dbLabels, $recordIDs);

    if(isset($result) && count($result) > 0){

        if (count($recordIDs)>2)
        {
          //Requesting nultiple records, this is not a form filler
          Log::error("DataProvider::loadrecords - Multiple records passed in.  Not implemented yet");
        }
        else
        {
          return $result[0];//Only return the first record
        }
    } else {
        Log::error("No record for for ID " . $recordIDs[0]);
        return null;
    }

  }
  /* Get one or more records WHERE ID in ($IDs) )
  */
  protected function _sql_GetRecord($preparedStatementName,$dbLabels, $IDs)
  {
    $recordsToGet = Util::Array2csv($IDs, $dbLabels);
    return self::GetInstance()->_sqlExecuteStatement($preparedStatementName, $IDs);
  }
  /* _sql_GetRecordWhere -
  @param: $recordsToGet = int[] array of records to get
  */
  protected function _sql_GetRecords($preparedStatementName, $recordIDs)
  {
    return self::GetInstance()->_sqlExecuteStatement($preparedStatementName, $recordIDs);
  }
  /*
  * Build an associative Access Control list for a user.
  * Keyed by resource, values are ACCESS LEVEL ENUM (INT) at type and record levels:

  *
  *  If a user delete, it is assumed they can update, if they can update, they can read ...
  *
  *     [ "resourceName" =>
  *        [ "\Warehouse\Constants\SessionVariableIndexes::ACL_DATA" => [
  *                 "READ_ONLY" => "15,16,17",
  *                 "UPDATE" => "18,19",
  *                 "DELETE" => "20, 21"
  *            ],
  *        ]
  *    ]
  */
  public static function GET_ACCESSIBLE_RESOURCES($userRecord){
    /*
     FOR EACH ACTIVE RESOURCE:
        -- Reference the already loaded Active Resources
        -- Look for ownership of a type, can the user add new Containers, Providers,

    */

    $returnData = array();
    $userID = $userRecord->ID;

    // ======== SETUP THE USER ============
    // USER IS ALWAYS A RESOURCE, Only an admin can add new Users.  This is
    //  outside of the controlled signup process, which allows create from webform

    $returnData["User"] = array();
    $returnData["User"][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA] = array();

    if ($userRecord->IsAdministrator){
      $returnData["User"][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA][\Warehouse\Constants\ResourceAccessType::FULL] = ["*"];
    }
    else{
      $returnData["User"][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA][\Warehouse\Constants\ResourceAccessType::UPDATE] = [PWH_SessionManager::GetCurrentUserID()];
      // TODO: Providers and Facility owners should be able to modify or at least read a worker's record
    }
    // ==================================

    // ======== SETUP THE ACCESS CONTROL LIST ============
    // Iterate the list of active resourceNames from ConfigurationManager
    //   Adding recordids which the user has access to:
    // This ACL is used to build menus for navigation and record selection.
    //   If the user is an owner of an item, they can delete
    //   If they are associated, they can only update


    $resourceNames              = ConfigurationManager::GetLoadedResourceNames();
    $alwaysAllowToAddResources  = ConfigurationManager::GetAlwaysAddResources();

    foreach ($resourceNames as $resourceName) {

          //// TODO: this should be moved to higher level and not added
          if ($userRecord->IsAdministrator)
          {
            $returnData[$resourceName] = array();
            $returnData[$resourceName][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA] = array( \Warehouse\Constants\ResourceAccessType::FULL => ["*"]);
          }
          else {
              //Don't even add it if they have no access
              $userCanAddRecordOfThisType = self::_userCanAdd($resourceName, $userID);
              if (  !$userCanAddRecordOfThisType &&
                    !in_array($resourceName, $alwaysAllowToAddResources)
                  ){
                    continue;
              }
              $returnData[$resourceName] = array();


              $resourceConfig = ConfigurationManager::GetResourceConfig($resourceName);

              Log::debug("==============GET_ACCESSIBLE_RESOURCES($userID) == Looking at CONFIG $resourceName ...");

              $ownedRecords = self::GetOwnedRecords($resourceName, $userID);

              $count = 0;
              if (!empty($ownedRecords)){
                $count = count($ownedRecords);
                $returnData[$resourceName][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA]           = array();
                $returnData[$resourceName][\Warehouse\Constants\SessionVariableIndexes::ACL_DATA][\Warehouse\Constants\ResourceAccessType::FULL]  = $ownedRecords;
              }
              $displayname = $userRecord->GetListItemText();
              Log::debug("==== SUMMARY --- THERE ARE $count records for $resourceName for user $displayname");
            }
        }

        return $returnData;
  }
  /*
  * Return ACL list for $resourceName
  *   returns = array('id', 'name') OR NULL if resource is not directly owned by a User resource
  */
  public static function GetOwnedRecords($resourceName, $userID){

    $resourceConfig     = ConfigurationManager::GetResourceConfig($resourceName);
    $resourceTableName = $resourceConfig['tableName'];

    if (!isset($resourceConfig['ownedByFieldName'])){
      return null;
    }

    $ownerFieldName = $resourceConfig['ownedByFieldName'];

    $ownerFields =  !strpos($ownerFieldName, ',') &&
                    isset($resourceConfig['fields'][$ownerFieldName]) ?
                    $resourceConfig['fields'][$ownerFieldName] : null;


    if ( is_null($ownerFields) || explode('.', $resourceConfig['fields'][$ownerFieldName]['linkedFieldKey'])[0] != 'User' ){

      //This is not a resource directly owned by a user
      return null;
    }
    else {

        return !empty(self::GetOwnedRecordsForResource( $resourceTableName,
                                                  $ownerFieldName,
                                                  $userID)) ?
                                                  self::GetOwnedRecordsForResource( $resourceTableName,
                                                                                            $ownerFieldName,
                                                                                            $userID) : null;
    }
  }
  /*
  *   For a given resourceName and userID, recursively look for ownership
  *     of at least one item of this type OR if owner is not a user, look
  *     until this user owns the parent
  */
  private static function _userCanAdd($resourceName, $userID)
   {
     /*
     //iterate the fields listed that describe the foreign ownership

     //DETERMINE IF THE USER IS ALLOWED TO ADD NEW INSTANCES OF A TYPE

     //User is allowed to add new records of this type ONLY if they are:
     //  an owner of ANY instance of the type of resource that owns this resource
     //
     //  i.e.  Containers can only be added by provider owners
     //        Users can be added by anyone (as part of signup)
     //        Facilities and Providers can only be added by admin currently
     //         (This is special logic added for better data control)
     //        Pallets, Containers, Bins - only by Provider OR Facility owner
     //  (So this means if you are a Provider, you can add any of these items)
     //        StorageItems can be added by anyone
     //
     */
      $userIsOwner = false;
      //Load the resource
      $resourceConfig = ConfigurationManager::GetResourceConfig($resourceName);

      if ($resourceName !== 'User'){
        Log::debug("OWNERSHIP SEARCH ===== Resource $resourceName is NOT user.");
        if (isset($resourceConfig['ownedByFieldName'])){

          $ownerFieldNames = explode(',' ,$resourceConfig['ownedByFieldName']);

          //Look at which fieldNames hold the foreign data, could be multiple
          //i.e.  Storageitem = 'ownerid' ...
          //      Container = 'facilityid,providerid'
          foreach ($ownerFieldNames as $ownedByFieldName) {
              $f=$resourceConfig['fields'][$ownedByFieldName]['linkedFieldKey'];
              Log::debug("OWNERSHIP SEARCH ==== LOOKING AT $f  how many fields ?");
              //Grab the resource definition this field represents
              $foreignResourceFields    = explode(".", $f);
              $foreignResourceName      = $foreignResourceFields[0];
              $foreignResourceFieldName = $foreignResourceFields[1];
              //i.e.  User.ID, Provider.ID , etc .. it's a resource property name

              if ($foreignResourceName!=='User'){
                Log::debug("OWNERSHIP SEARCH $resourceName ===== Resource $foreignResourceName WAS NOT a User. Contining to look ... ");
                return self::_userCanAdd($foreignResourceName,$userID);
              }
              else {
                $resourceTableName = $resourceConfig['tableName'];
                $sql = "SELECT `id` FROM `$resourceTableName` WHERE `$ownedByFieldName`=$userID";
                Log::debug("OWNERSHIP SEARCH FINAL CHECK ===== $sql");
                $userIsOwner = self::GetInstance()->RecordsExist($sql);

                $b = $userIsOwner ? "an" : "not an";
                Log::debug("User $userID is $b owner of at least one $resourceName");

                // NOTE: userisowner is over-written for each foreign ownership,
                //    , no need to track here, so should I join instead ?
              }
          }
        }
      }
      return $userIsOwner;
   }

  /*
  *   Given a resource, get all IDs owned by $userID
  *   @returns: array('id', 'name')
  */
  private static function GetOwnedRecordsForResource($foreignresourceTableName,
                                                      $foreignResourceFieldName,
                                                      $userID){
    $fieldSQLForDisplay = "name"; // TODO: move this to config per resource, right now name seems to work
    return self::GetInstance()->_getOwnedRecordsForResource($foreignresourceTableName,
                                                            $foreignResourceFieldName,
                                                            $fieldSQLForDisplay,
                                                            $userID);
  }
  /*
  *   Given a resource, return all foreign resource associated items (keyed by foreign resourceName) owned by $userID
  *
  *   1.) Grab all the fieldNames in the association tables keyed by remote resourceName
  *   2.) For each of these remote resources, get all owned records of that type, then look
  *        for that $resource in the association table
  *
  *  i.e.  This can be used to find the containerID
  */
  public static function GetAssociatedRecordsForResource($resourceName, $userID){

      $associations = array();
      $associatedRecordIDs = array();

      $associationFieldNames = ConfigurationManager::GetAssociationFieldNamesForItem($resourceName);

      //SELECT storageitem.name from storageitem JOIN storagecontainerinventory ON storagecontainerinventory.containerid=storageitem.id WHERE storageitem.ownerid=361
      $fieldSQLForDisplay = "name"; // TODO: move this to config per resource, right now name seems to work

      foreach ($associationFieldNames as $foreignResourceName => $associationTableField) {

        $associationTableFields = explode(".", $associationTableField);
        $foreignresourceTableName = $associationTableFields[0];//storagefacilityinventory
        $foreignResourceFieldName = $associationTableFields[1];//itemid

        //Grab
        $associatedRecordIDs = self::GetInstance()->_getAssociatedRecordsForResource($foreignresourceTableName,
                                                                      $foreignResourceFieldName,
                                                                      $fieldSQLForDisplay,
                                                                      $userID);
      }

      return $associatedRecordIDs;
  }

  //// ---- TEMP NON-GENERIC METHODS ADDED FOR SCHEDULE.  These like everything else, can be generalized




// TODO: These can be generalized

  /*
  *   Find where items are stored for a user
  *
  * @param: $userID - the userID
  * @param: $resourceName - name of the resource : Container
  *
  * @returns: array[$foreignResource] = recordIDs[]
  */
  public static function GetStoredItemsForUser($userID, $resourceName){

    $associationFieldNames = ConfigurationManager::GetAssociationFieldNamesForItem($resourceName);
    //SELECT storageitem.name from storageitem JOIN storagecontainerinventory ON storagecontainerinventory.containerid=storageitem.id WHERE storageitem.ownerid=361

    foreach ($associationFieldNames as $foreignResourceName => $associationTableField) {

      //self::
    }
  }

}
