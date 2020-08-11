<?php

require_once "WarehouseBaseType.php";

class User extends WarehouseBaseType
{
  //// TODO: implement static ctor, move more items to static
  public static $Tablename = "user";

  /*
    If true will have full access
  */
  public $IsAdministrator = false;



  // Construct a User object populated from
  // @param $ID = Int - DB Record.ID or null for new record

  function __construct($ID=null){

      $resourceJSONObject = ConfigurationManager::GetResourceConfig(get_class(),true);
      $c = get_class();
      if (empty($resourceJSONObject)){
        Log::error("Resource CONSTRUCT - CONFIG IS EMPTY FOR $c ");
      }
      parent::__construct($resourceJSONObject, $ID);

      //set overrides here i.e. - for form
      //$this->_formPath = "forms/form{$this->Classname}.php";

      // NOTE this is the user for this resoursce NOT the currenly logged in user
      //references adminusers table, sets property for THIS user, not logged in one
      if (!empty($ID)){
        $this->IsAdministrator = DataProvider::IsUserAdmin($ID);
      }

      //This is checking logged in user NOT this record
      if (!PWH_SessionManager::IsAdministrator()){
        $this->FormTitle = "My Profile";
      }
  }
  /*
    GetSelectOptionItemText
     - Given an db results array, built a select option line item
  *
  */
  public function GetSelectOptionItemText($record)
  {
    return $record["firstname"] . " " . $record["lastname"];
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
  public function GetListItemText()
  {
    return $this->DB_Fields["firstname"] . " " . $this->DB_Fields["lastname"];
  }
  public function GetDisplayText(){
    return $this->GetListItemText();
  }

  /*
    return new instance of this object
  */
  public function GetNewInstance()
  {
    return new User(0);
  }

  /*
  * Get Resources this user has access to.
          If not present already in user properties OR if a force-reload is requested,
          a requery will be triggered, which builds it from the Database






  */
  public function GetResourcesForUser(){
    return DataProvider::GET_RESOURCES($this->ID);
  }
  /* Show an HTML form, ready to post to $this->Classname (User, StorageItem, ...)
  */
  public function ShowFormNavigationSelect()
  {
    //If they are an admin, only then can they see all users and edit them
    if (PWH_SessionManager::IsAdministrator()){
      return parent::ShowFormNavigationSelect();
    }
  }
}
