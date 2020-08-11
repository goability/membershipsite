<?php
/*
* ConfigurationManager
*   - Load configuration items from statically configured file
*   -
*/

class ConfigurationManager{

  //Singleton Instance of the Configuration object
  private static $_configuration = null;

  // TODO: pull from config
  public static $NavTopStaticConfigItems = array (
                        "Signup" => array("displayText" => "Join",
                                          "url" => "/Signup",
                                          "classes"       => "fa fa-join",
                                          "resourceName" => ""),
                        "Login" => array( "displayText" => "Login",
                                          "url" => "/Login",
                                          "classes"       => "fa fa-sign-in",
                                          "resourceName" => "")
                     );

  /*
    Static constructor
    Load the configuration and resources into this instance.

  */
  public static function LoadStaticConfigurations(){

    if ( is_null(self::$_configuration)){

      Log::debug("configuration was null");

      //Make sure directory exists
      $configDirectory = getcwd() . DIRECTORY_SEPARATOR . "config";
      if (!file_exists($configDirectory)){

        $configDirectory =  getcwd() . DIRECTORY_SEPARATOR .
                            ".." . DIRECTORY_SEPARATOR . "config";
            if (!file_exists($configDirectory)){
              Log::error("directory $configDirectory does not exist either. exiting!");
              echo "ERROR WITH SYSTEM CONFIGURATION";
              die();
            }

      }
      //todo this is loaded with every page, session doesn't start till login
      self::$_configuration = new Configuration($configDirectory);
      self::$_configuration->LoadResourceConfigItem("User");//this one is always loaded
    }
  }
  /*
  * Returns true if there is more than one resource
  */
  public static function HasResources(){
    return  !is_null(self::$_configuration)           &&
            !empty(self::$_configuration->Resources)  &&
            count(self::$_configuration->Resources) > 1;//User is already there
  }
  /*
    Return a configuration resource item, and also load it statically so
    that it can be used again in the flow if needed
  */
  public static function GetResourceConfig($name, $forceReload=false){

      Log::debug("CONFIG - Requesting to load the configuration for $name");

      if (!isset(self::$_configuration->Resources[$name]) || $forceReload){
        Log::debug("CONFIG - Loading the configuration for $name");
        self::$_configuration->LoadResourceConfigItem($name);
      }
      if (!isset(self::$_configuration->Resources[$name])){
        Log::debug("CONFIG ----- DID NOT SET");
      }
      return isset(self::$_configuration->Resources[$name]) ?
                  self::$_configuration->Resources[$name] : [] ;
  }
  /*
  *  Get a string array of currently loaded resources OR null
  *
  */
  public static function GetLoadedResourceNames(){
    return self::$_configuration->ResourceNames;
  }
  /*
  * Get an string array of resourceNames that should always be added, such as Storageitem
  */
  public static function GetAlwaysAddResources(){
    return self::$_configuration->AlwaysAllowToAddResources;
  }
  /*
  *  Get a string array of currently loaded resources with navigation
  *    parameters used to describe the resource
  *
  */
  public static function GetLoadedResourceNavConfigItems($accessibleResourceNames){


    $resourceNavConfigItems = array();//User is always available

    foreach (self::$_configuration->Resources as $resourceName => $resourceConfigItem) {

      if (!in_array($resourceName, $accessibleResourceNames))
      {
        Log::debug("BUILD MENU - User does not have access to $resourceName");
      }
      else {
        $resourceNavConfigItems[$resourceName] =
                  array(  "resourceName"  => $resourceName,
                          "url"           => $resourceConfigItem["navigationMenuURL"],
                          "displayText"   => $resourceConfigItem["navigationMenuText"],
                          "resourceImageLarge" => $resourceConfigItem["resourceImageLarge"]
                        );
      }
    }

    return $resourceNavConfigItems;
  }
  /*
  * Load from disk all of the active resource .json files
  */
  public static function LoadAllResourceConfigs(){
      Log::info("======= LOADING ALL RESOURCES ==============");
      self::LoadStaticConfigurations();
      self::$_configuration->LoadAllResourceConfigs();
  }
  /*
  * Loads all from disk and returns it
  */
  public static function GetConfigurationObject(){
    if (empty(self::$_configuration)){
      self::LoadAllResourceConfigs();
    }
    return self::$_configuration;
  }
  /*
  * Get a count of loaded resources
  */
  public static function GetLoadedResourceCount(){

    return count(self::$_configuration->Resources);
  }
  /*
  * For a configured resource, return the field that holds the
  *   foreign resourceid that owns this resource
  */
  public static function GetOwnedByFieldName($resourceName){
    return self::$_configuration->_getOwnedByFieldName($resourceName);
  }
  /*
  *  Get the name of the User table (from configuration)
  */
  public static function GetUserTableName(){
    return is_null(self::$_configuration) ?
                                  null :
                                  self::$_configuration->UserResourceTableName;
  }
  /*
  *  Given a resource, return the table.fieldName that can hold instances of this type
  *
  * @param: $resourceName  name of the resource
  * @returns: array of table.fieldnames, keyed by the associated resourceName
  *
  *  i.e.  $resource = 'Storageitem' will return --> [Storagecontainer] = "storagepalletinventory.itemid"
  */
  public static function GetAssociationFieldNamesForItem($resourceName){

    $associations = array();
    foreach (self::$_configuration->Resources as $configuredResourceName => $resourceConfigItem) {
        if ( isset($configuredResourceName["associativeCollections"])){
          foreach ( $configuredResourceName["associativeCollections"] as
                      $associativeCollectionName => $associativeCollectionName) {
            if ( key_exists($resourceName, $associationObject)){
              $associations[$configuredResourceName] = $associationObject[$resourceName]['LinkedFieldName'];
            }
          }
        }
    }
    return $associations;
  }
}
