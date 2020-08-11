<?php

require_once "WarehouseBaseType.php";

class Storagefacility extends WarehouseBaseType
{
  //// TODO: implement static ctor, move more items to static
  public static $Tablename = "storagefacility";

  /*
  * Construct an object populated from
  * @param $ID = Int - DB Record.ID or null for new record
  */
  function __construct($ID=null){
      $resourceJSONObject = ConfigurationManager::GetResourceConfig(get_class());

      if (empty($resourceJSONObject)){
        Log::error("ERROR FATAL RESOURCE CONSTRUCT ========= EMPTY resourceJSONObject for $resourceType ");
        die();
      }
      else{
        parent::__construct($resourceJSONObject, $ID);
        $this->FormTitle = $this->DisplayName = "Storage Facility";
      }
  }

  /*
    GetSelectOptionItemText
     - Given an db results array, built a select optin line item
  *
  */
  public function GetSelectOptionItemText($record)
  {
    return $record["name"];
  }
  /*
    GetSelectListItemText
     - Given an db results array, built a list optin line item
  *
  */
  public function GetSelectListBoxItemText($record)
  {
    return GetSelectOptionItemText($record);
  }
  /*
    return new instance of this object
  */
  public function GetNewInstance()
  {
    return new Storagefacility(0);
  }
}
