<?php

require_once "WarehouseBaseType.php";

class Provider extends WarehouseBaseType
{
  //// TODO: implement static ctor, move more items to static
  public static $Tablename = "provider";

  // Construct an object populated from
  // @param $ID = Int - DB Record.ID or null for new record
  function __construct($ID=null){
      parent::__construct(ConfigurationManager::GetResourceConfig(get_class()), $ID);
      $this->_formPath = "forms/formProvider.php";
  }

  /*
  *  GetSelectOptionItemText
  *   - Given an db results array, built a select optin line item
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
    return new Provider(0);
  }
}
