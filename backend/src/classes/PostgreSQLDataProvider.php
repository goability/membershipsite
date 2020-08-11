<?php
require_once "DataProvider.php";
/*
PostgreSQL DataProvider
*/
class PostgreSQLDataProvider extends DataProvider {

  function __construct() {
    parent::__construct();

  }
  /*
   Make the DB connection, and set the local property
  */
  protected function _connect(){

    $fail_message = "Connection failed ";

    $pg_port              = DATABASE_PG_PORT;
    $pg_host              = DATABASE_PG_HOST;
    $this->database       = DATABASE_PG_NAME;
    $pg_user              = DATABASE_PG_USER;
    $pg_password          = DATABASE_PG_PASSWORD;

    $pg_connectionString  = "host=$pg_host dbname=$this->database user=$pg_user password=$pg_password";


    try {
      Log::info("Connecting to PGSQL $pg_connectionString");
      $this->_dbConnection = pg_connect($pg_connectionString);
    }
    catch (Exception $e) {
      $fail_message .= " " . $e->getMessage();
    }

    if (!empty($this->_dbConnection)){
      Log::info("Connected to " . DATABASE_TYPE);
    }
    else{
      die($fail_message);
    }
  }

  /*
    Prepare a single DB statement
  */
  protected function prepareSingleStatement($statementName, $statementString)
  {
    $success = true;
    try {
      if(pg_prepare($this->_dbConnection, $statementName,  $statementString))
      {
          Log::info("SUCCESSFUL PREPARE FOR $statementName - $statementString");
      }
      else
      {
        Log::error("Error preparing $statementName using  " . $statementString);
      }

    } catch (\Exception $e) {
        Log::error("Error preparing statement" . $e->getMessage());
        $success = false;
    }
    return $success;
  }

  /*
  insert a new record
  */
  public function insertrecord($resourceName, $fieldData){
    return $this->_sqlExecuteStatement(Warehouse\Constants\SqlPrepareTypes::SQL_INSERT . $resourceName, $fieldData );
  }
  public function updaterecord($resourceName, $fieldData){
    return $this->_sqlExecuteStatement(Warehouse\Constants\SqlPrepareTypes::SQL_UPDATE . $resourceName, $fieldData );
  }

  /*  _sqlExecuteStatement - Execute the $sql statement
  @param $prepareStatementName - name of the previously prepared statement
  @param $queryParameters - string array of parameters in order of indexes in prepared statement
  @returns records[]
  */
  protected function _sqlExecuteStatement($preparedStatementName, $queryParameters)
  {
      $rows = null;

      try {
        $result = pg_execute($this->_dbConnection, $preparedStatementName, $queryParameters);

        if (!$result)
        {
          Log::error("Error executing SQL statement $preparedStatementName");
        }
        else{
          $rows = pg_fetch_all($result, PGSQL_ASSOC);
        }

      } catch (\Exception $e) {
          Log::error($e->getMessage());
      }

      return $rows;
  }
}
