<?php

require_once "WarehouseBaseType.php";

class Storagebin extends WarehouseBaseType
{
  //// TODO: implement static ctor, move more items to static
  public static $Tablename = "storagebin";

  // Construct an object populated from
  // @param $ID = Int - DB Record.ID or null for new record

  function __construct($ID=null){
      parent::__construct(ConfigurationManager::GetResourceConfig(get_class()), $ID);
      $this->FormTitle = "Bins";
      $this->DisplayName = "Bin";

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
    return new Storagebin(0);
  }
}
