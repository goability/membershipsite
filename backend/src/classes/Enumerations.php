<?php
/*
Enumerations are defined using abstract classes with concrete members
*/
//Types of common selects - used for common prepare statement identifiers

namespace Warehouse\Constants;

/*
Used by resources to determine which nav-bar to show
NAVBAR_RECORD_NAV = main record add/new bar
NAVBAR_RECORD_ASSOCIATION = submenu for managing associations for this record
NAVBAR_RECORD_LINKED = submenu for managing linked foreign keys for this record
*/
abstract class UI_NavigationTypes{

  public const NAVBAR_NONE                  = 1; //no link, just a list
  public const NAVBAR_RECORD_NAV            = 2;
  public const NAVBAR_RECORD_ASSOCIATION    = 3;
  public const NAVBAR_RECORD_DISASSOCIATION = 4;
  public const NAVBAR_RECORD_LINKED         = 5;

}
/*
  An Access level for a resource
   - Can be applied at Type or Record Level
   i.e. A user can have AddOnly for an association, but can not edit the record

   to determine if a record has a certain level, just AND it:

   ResourceAccessType::CREATE & number;
     number is set to CREATE only if they have access to it.
     All access privs are added together in the DataProvider GetResourcesForUser

*/
abstract class ResourceAccessType{

  public const NONE       = 0; // No access to this resource
  public const CREATE     = 2; // Create new (type level), Clone (record-level)
  public const READ       = 4; //Never able to update/delete/create this resource
  public const UPDATE     = 8; //Only able to Add new items
  public const DELETE     = 16; //Can delete
  public const FULL       = 32; //Full access
}

abstract class SqlPrepareTypes{

  /*
    These tags serve as a base and are concated with other identifiers such
    as a table_name, or collectionName for uniqueness.

    SELECT/UPDATE Examples:
      SQL_SELECT_INuser with SELECT * FROM warehouse.user WHERE id IN (?)
      SQL_SELECT_GREATERuser with SELECT * FROM warehouse.user WHERE id > ?
      SQL_UPDATEstoragefacility = UPDATE warehouse.storagefacility SET
            ownerid = ?,name = ?,address = ?,city = ?,state = ?,zip = ?,
            website = ?,emailaddress = ?,phone = ?,lat = ?,lng = ?,notes = ?
            WHERE id = ?

    Example for an associative lookup table facilityowners {facilityid, userid}
      SQL_SELECT_WHERE_FIELDstoragefacility.facilityowners.User =
          SELECT * FROM user
            JOIN storagefacilityowners
            ON storagefacilityowners.userid =  user.id
            WHERE storagefacilityowners.facilityid IN (?)

    Database:

    MySQL and PostgreSQL handle things slightly differently in terms of tokens

    MySQL:  Build the prepare statement and reference it locally, sending it
    along with your query.  Statement objects will be stored in the MySQLDataProvider

    PostgreSQL: Build the prepare statement, give it a string name and actually
    store it into the DB.  Future usage of the predefined statement is called upon
    using an execute statement.  No need to store the prepare templates locally once
    stored in the DB, however DB will warn if there are duplicates
  */
  public const SQL_SELECT_IN            = "SQL_SELECT_IN";
  public const SQL_SELECT_WHERE_FIELD   = "SQL_SELECT_WHERE_FIELD";
  public const SQL_SELECT_ASSOC         = "SQL_SELECT_ASSOC";
  public const SQL_SELECT_GREATER       = "SQL_SELECT_GREATER";
  public const SQL_SELECT_LESS          = "SQL_SELECT_LESS";
  public const SQL_INSERT               = "SQL_INSERT";
  public const SQL_DELETE               = "SQL_DELETE";
  public const SQL_UPDATE               = "SQL_UPDATE";

  //Updating/Getting password hash from DB
  public const SQL_AUTH_GET             = "SQL_AUTH_GET";
  public const SQL_AUTH_UPDATE          = "SQL_AUTH_UPDATE";

}

/*
A type of report
Resource level would be a list of all records for a type
Record level would show associations and linked records for a specific record
NOTE:  These tags must be the same as in config
*/
abstract class ReportType
{
  public const RESOURCE = "Resource";
  public const RECORD   = "Record";
}

/*
  Type of token granted
*/
abstract class TokenType
{
  public const LOGIN_REQUEST  = 1;
  public const PASSWORD_RESET = 2;
}
/*
* Names of session variables
*/
abstract class SessionVariableNames{
  public const CONFIG_SITE      = "ConfigSite";//site level configs and resource defs
  public const RESOURCE_ACL     = "ResourceACL";//array of recordIDs this user has access to, keyed by resourceName

}
abstract class SessionVariableIndexes{
  public const RECORDS          = "Records"; // holds records for something
  public const ACL_DATA         = "ACL_Data"; //holds the acl array, keyed by ResourceAccessType
}
