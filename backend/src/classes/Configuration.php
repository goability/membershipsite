<?php

class Configuration{


  //THIS IS THE ONLY PLACE THE CONFIG FILE PATH IS SET
  private $_filePathDirectory;

  //json object from /config/SiteConfiguration.json

  private $_configurationData;

  // List of resource Names
  public $ResourceNames = array();

  // List of resource names that users can always add new instances of
  //   without verification that they are an owner of at least one item that owns one of these types
  public $AlwaysAllowToAddResources = array();

  public $Resources = array();

  public $UserResourceTableName = 'user';//This is over-written when loaded

  /*
  * Load the site configuration object, which has the requested resource Names
  */
  public function __construct($filePathDirectory){

      Log::debug("==================");
      $this->_filePathDirectory = $filePathDirectory;

      Log::warning("DISK READ - Loading Site Configuration");

      $jsonString = file_get_contents(  $filePathDirectory .
                                                        DIRECTORY_SEPARATOR .
                                                        "SiteConfiguration.json"
                                                      );
      $configurationData = json_decode($jsonString);

      if (null===$configurationData){
        echo "SYSTEM CONFIGURATION ERROR";
        die();
      }
      else{
        Log::debug("CONFIG - Setting a list of the active resourceName ");
        foreach ($configurationData->activeResources as $resourceName) {
          $this->ResourceNames[] = $resourceName;
        }
        foreach ($configurationData->alwaysAddResources as $resourceName) {
          $this->ResourceNames[] = $resourceName;
          $this->AlwaysAllowToAddResources[] = $resourceName;
        }
      }
  }

  /*
  * Get the JSON Resource definition for a resource
  * @param: $name - name of the resource (As defined in the config)
  * @returns:  nothing, this is a setter
  */
  public function LoadResourceConfigItem($name)
  {
    //Load the json config file for this object
    // TODO: LOOK CLOSER at reading this each time, memcache, etc ? for file ?
    //     for now, just stick in static array to reduce reloading during one page load at least

    $pathToFile = $this->_filePathDirectory .
                  DIRECTORY_SEPARATOR . "resources" .
                  DIRECTORY_SEPARATOR .  "resource_$name.json";
    if (!file_exists($pathToFile)){
      $err = "SETUP ERROR - Configuration File did not exist $pathToFile";
      Log::error($err);
      echo $err;
      die();
    }
    Log::warning("DISK READ DISK READ  - Loading file $pathToFile from disk!!");
    $jsonString = file_get_contents($pathToFile);

    $this->Resources[$name]  = json_decode($jsonString, true);

    Log::info("Loaded resource $name into Configuration.");

    if ($name==='User')
    {
      $this->UserResourceTableName = $tablenameUser = $this->Resources[$name]['tableName'];
      Log::debug("TABLE NAME FOR USER SET TO $tablenameUser");
    }
  }
  public function _getOwnedByFieldName($resourceName){
    return isset($this->Resources[$resourceName]["ownedByFieldName"]) ?
      $this->Resources[$resourceName]["ownedByFieldName"] : null;
  }
  /*
  * For all of the previously loaded ResourceNames, Load the resource config
  */
  public function LoadAllResourceConfigs(){
    foreach ($this->ResourceNames as $resourceName) {
      Log::debug("LOADING $resourceName");
      $this->LoadResourceConfigItem($resourceName);
    }
  }
  /* Returns true if more than 1 (user =)
  *
  */
  public function GetLoadedResourceCount(){
    return count($Resources) > 1;
  }
}
