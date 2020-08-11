<?php
require_once "Constants.php";
require_once "DataProvider.php";
require_once "Util.php";
require_once "Enumerations.php";
require_once "UIManager.php";

class WarehouseBaseType
{

  public   $DB_Fields; //database fields and VALUES, keyed by DB Columnname
  public   $DB_Labels             = []; //labels that go on the form, keyed by DB Columnname
  public   $DependentResources    = []; //Linked external labels (Like StorageItems at a location)
  public   $DependentResourceData = []; //filled with records
  public   $ReportConfig          = [];//Holds column and reporting details
  public   $Associations          = [];//Linked collections using an associative table
  public   $AssociationData       = [];//filled with records
  private  $_databaseName;//name of the database

  public   $DisplayName;//can be over-riden for spaces

  public   $ImageFilename;


  // fields that should not be overridden
  public    $ID = 0; //Assume a new record=0, unless loadrecords finds a matching one
  public    $Name;
  protected $timestamp;//last accessed
  public    $FormTitle;//Title to show on CRUD forms
  public    $FormProperties;//[action, showCancel, mode, ...
  public    $Classname;//set in ctor using this of derived class
  public    $FormMode = "";
  private   $Cached_Records = [];//TODO Array of Cached (Types)
  public    $_indexFieldName = "id";

  public    $tableName;
  //TODO more move things to static

  public static $IndexFieldname;
  public static $TypeName;

  public static function CreateFromRecord($record)
  {
    $resourceName = get_called_class();
    $instance = new $resourceName();
    //A record was passed in, create the object using this data
    $instance->DB_Fields  = array_replace($instance->DB_Fields, $record);
    $instance->ID         = $instance->DB_Fields[$instance->_indexFieldName];

    return $instance;
  }
  /*
  Create a new Generic Object
  @param $dataJSONObj - JSON object describing the resource
  @param $recordID - Load a specific record OR null for new record
  @param $record - pass in an associative array and populate the object
  */
  function __construct($resourceJSONObject, $recordID=null)
  {
    $resourceType = get_called_class();
    if (!isset($resourceJSONObject["tableName"])){
          Log::error("ERROR FATAL RESOURCE CONSTRUCT ========= EMPTY resourceJSONObject for $resourceType ");
          die();
    }

    $this->Classname = $this->Name = $resourceType;

    $resourceType::$TypeName = $resourceType;

    //Setting this at static level // TODO: look to refactor
    $this->tableName = $resourceType::$Tablename = strtolower($resourceJSONObject["tableName"]);
    Log::debug(" =====CREATING TYPE $resourceType with tableName $this->tableName");

    $resourceType::$IndexFieldname = empty($resourceJSONObject["indexFieldname"]) ? "id" : $resourceJSONObject["indexFieldname"];

    $this->_databaseName = DATABASE_TYPE === DATABASE_MYSQL ? DATABASE_MYSQL_NAME : DATABASE_PG_NAME;
    $this->_indexFieldName = !empty($resourceJSONObject["IndexFieldName"]) ? $resourceJSONObject["IndexFieldName"] : $this->_indexFieldName;

    //Populate DB_Fields and DB_Labels using properties from configuration
    // Keys for both will match DB column names

    // Fields holds values, so just fill it empty "" for now
    $this->DB_Fields = array_fill_keys(array_keys($resourceJSONObject["fields"]), "");

    // Labels are populated with data from config describing the data-type and form labels to show
    $this->DB_Labels = array_replace($this->DB_Labels, $resourceJSONObject["fields"]);

    if (array_key_exists("dependentCollections", $resourceJSONObject))
      $this->DependentResources = array_replace($this->DependentResources, $resourceJSONObject["dependentCollections"]);

    if (array_key_exists("associativeCollections", $resourceJSONObject)){
        $this->Associations = array_replace($this->Associations, $resourceJSONObject["associativeCollections"]);

    }
    if (array_key_exists("reporting", $resourceJSONObject)){
        //Reports contain what to show for a report for this resource
        $this->ReportConfig = array_replace($this->ReportConfig, $resourceJSONObject["reporting"]);
    }
    $this->_formPath = "forms/formGeneric.php";
    $this->FormTitle = $this->DisplayName = $this->Classname;



    //Add a list of prepared DB statements for this resource TODO // OPTIMIZE:  this

    $this->_buildCommonPreparedStatements();

    //If a recordID comes in as null, that is a new record
    if (is_numeric($recordID) && $recordID>0)
    {
      if ($this->_loadRecords([$recordID])){
        $this->ID = $recordID;
        $this->ImageFilename = isset($this->DB_Fields["imagename_main"]) ?
              $this->DB_Fields["imagename_main"] : null;
              
        if(!empty($this->Associations)){
          $this->AssociationData = DataProvider::GetAssociatedRecords($this);
        }
        if(!empty($this->DependentResources))
        {
          $this->DependentResourceData = DataProvider::GetDependentRecords($this);
        }
      }
    }

  }
  //Return JSON Representation
  public function toJSON()
  {
    //Simply load the record into this object
    return json_encode($this->DB_Fields);
  }
  /*
    Add common prepare statements for this resource
  */
  private function _buildCommonPreparedStatements(){

    Log::info("Adding Common Prepared StatementStrings for " .  $this->tableName);
    DataProvider::AddCommonPreparedStatementStrings($this);

  }
  //Get One record by name
  public function GetField($name)
  {
    Log::info("Getting field $name from " . $this->tableName);
    Log::info($this->DB_Fields[$name]);
    return $this->DB_Fields[$name];
  }

  //////////////////////////////////
  // DATABASE Functions
  /*
  Load one or more records.
  If one record, values are copied into $this->DB_Fields
  For multiple records, only the index and displays will be stored in _UserCollection Object

  */
  protected function _loadRecords($recordIDs)
  {
    $errMsg = "Error loading records for resource ID $recordIDs[0]";

    //TOD implement caching
    if (count($recordIDs)>2)
    {
      //Requesting nultiple records, this is not a form filler
      Log::error("NOT IMPLEMENTED YET Multiple records passed in.");
      return false;
    }

    if ( !isset($recordIDs) || (count($recordIDs)==1 && $recordIDs[0]==0) ){
        Log::error($errMsg);


        return false;
    }

    try {

          $row = DataProvider::LOAD(Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_IN . get_called_class()::$Tablename, $this->DB_Labels, $recordIDs);
          if (count($row)==0)
          {
            Log::error("No record for ID $recordIDs[0]");
            $this->ID = 0;
            return false;
          }
          $this->DB_Fields  = array_replace($this->DB_Fields, $row);
          $this->ID         = $this->DB_Fields[$this->_indexFieldName];

          // REMOVE FIELDS THAT DON'T NEED TO BE EDITED
          //  This affects all transactions because the form elements in config
          //   MUST match the items returned by the database, so these are removed
          // TODO: Do this cleaner with an array_intersect
          if (isset($this->DB_Fields['upasswd'])){
            unset($this->DB_Fields['upasswd']);
          }
          if (isset($this->DB_Fields['verified'])){
            unset($this->DB_Fields['verified']);
          }
          if (isset($this->DB_Fields['verified_timestamp'])){
            unset($this->DB_Fields['verified_timestamp']);
          }

          //remove the ownerid from the form and , it is this user
          if (!PWH_SessionManager::IsAdministrator()){
            $ownedByFieldName = ConfigurationManager::GetOwnedByFieldName($this->Name);
            if (isset($this->DB_Fields[$ownedByFieldName])){
              //  unset($this->DB_Fields[$ownedByFieldName]);
            }
            if (isset($this->DB_Labels[$ownedByFieldName])){
              unset($this->DB_Labels[$ownedByFieldName]);
            }
          }
        }
    catch (Exception $e){

      Log::err($e);
      return false;
    }
    return true;
  }
  /*
    Sets provided dictionary into DB
    $fieldData[$fieldName]=value WHERE $fieldName is same as DB FieldName
  */
  public function InsertRecord($fieldData)
  {
    //first copy the form data into this local object
    $this->DB_Fields = array_replace($this->DB_Fields, array_intersect_key($fieldData, $this->DB_Labels));

    return DataProvider::INSERT(get_called_class()::$Tablename, array_values($this->DB_Fields));
  }
  public function UpdateRecord($fieldData){
    $this->DB_Fields = array_replace($this->DB_Fields, array_intersect_key($fieldData, $this->DB_Labels));
    $this->DB_Fields['id'] = $this->ID;
    return DataProvider::UPDATE(get_called_class()::$Tablename, array_values($this->DB_Fields));
  }
  /**
  * Delete one record where ID=$Id
  */
  public function DeleteRecord($Id)
  {
    return DataProvider::DELETE(get_called_class()::$Tablename, $Id);
  }
  /*
    Get one resource record
    @param: recordID
    @return: resource properties
  */
  public function GET($id)
  {
    $this->_loadRecords([$id]);
    return $this->Properties;
  }
  /////////////////////////
  // Getters and Setters
  final protected function getClass()
  {
    return trim(get_class($this));
  }
  public function SetFormProperties($props)
  {
    $this->FormProperties = $props;
    $this->FormMode = $props["MODE"];
  }
  public function GetFormProperty($name)
  {
    $returnValue = null;
    if (key_exists($name, $this->FormProperties))
    {
      $returnValue = $this->FormProperties[$name];
    }
    return $returnValue;
  }
  ////////////////////////////////////////////
  // Public Functions
  /*
  Show the Record Form in HTML
  @param ID - recordID
  @includes form for the resource
  */
  public function ShowForm()
  {
    global $formProperties; //array
    include($this->_formPath);
  }


  /*
  Build an html div with list of records
  @param: resources - array of resource objects (Users, StorageItems, etc)
  @param: componentID - ID and name to set as the component on the HTML form
  @param: size number of items, defaults to 4
  @param: navbar - top navigation bar for the set.  Add, ..
  @param: navbarItem - item level navigation bar.  Delete, ..
  @param: $callbackFuncName - name of a function to call to add a menu for each record
  @param: $callbackFuncData - data to pass into that function (ORDERED)

  */
  static function showRecordsAsList($elementID, $resources, $size=4, $navbar=null, $callbackFuncName=null, $callbackFuncData=null){

    //                $dissassociateData = array("recordID"=>$foreignKeyValue);
    $retHTML = "<div class='container-fluid'>";
    $resourceName = get_called_class();

    if (!empty($navbar))
    {
      $retHTML .= "<div class='container-fluid'>";
      $retHTML .= $navbar;

      $retHTML .= "</div>";
    }
    $retHTML .= "<div class='container-fluid' style='margin:0; padding:0;' id='$elementID" . "DIV'>";
    $retHTML .= "<ul class='list-group' style='margin:0; padding:0;' id='$elementID" . "LIST'>";


    foreach ($resources as $resource) {

      $retHTML .= "<li id='$elementID-item-$resource->ID' class='list-group-item' aria-hidden='true' style='margin:0; padding:1;'>";
      //Attach the click handler to this line item, and add this resourceID
      $retHTML .=  !empty($callbackFuncName) ?
                      call_user_func_array(array(__NAMESPACE__ . "\UIManager",
                                            $callbackFuncName),
                                            array_merge($callbackFuncData, array($resource->ID))
                                            ) : "";

      $retHTML .= $resource->GetListItemText();
      $retHTML .= "</li>";
    }

    $retHTML .= "</ul></div>";

    return $retHTML;

  }
  /*
  public function addNavBar($navBarType, $data)
  {
    $retHTML = '';
    switch ($navBarType) {
      case Warehouse\Constants\UI_NavigationTypes::NAVBAR_RECORD_DISASSOCIATION:
        $retHTML .= "<i class='fa fa-trash'></i>";
        break;

      default:
        // code...
        break;
    }
    return $retHTML;

  }*/
  public function GetListItemText()
  {
    return isset($this->DB_Fields["name"]) ?
              $this->DB_Fields["name"] : null;
  }
  public function GetDisplayText()
  {
    return isset($this->DB_Fields["name"]) ?
              $this->DB_Fields["name"] : null;
  }
  public function GetNavbarAssociation($type)
  {

  }

  /* Build a drop-down selection of selectable records
    @param where - specify record to be selected
    @returns string HTML select component
  */
  public function ShowSelectRecordNavigation()
  {
    $accessibleRecordIDs = PWH_SessionManager::GetAccessibleRecordIDs($this->Name);

    return $this->buildSelectCombo(true, $accessibleRecordIDs);
  }

  /*
    @param $isFormNavigation - If true, indicates this component should change
    the form when values are selected, used as a record navigator
    @param $greaterThanIndex=0 - lowest recordID to show
    @param $selectedID=null - ID to set as selected in the component
    @param $componentID=ID of this component on the html form
    @param $size - default number of rows
    @param $multiple - allow multiple selection
    @param $rowdata = optional array of data to show instead of looking up in database

  */

  public function buildSelectCombo($isFormNavigation=false,$rowdata=null, $greaterThanIndex=0, $selectedID=null, $componentID="ID", $size=null, $multiple=false)
  {

    $ret = "";

    if (empty($rowdata) || $rowdata[0]==='*'){
        $rowdata = DataProvider::GET(Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_GREATER . get_called_class()::$Tablename, [$greaterThanIndex]);
    }
    if ($isFormNavigation)
    {
      $ret .= "<span class='form-control-label'></span> ";
      $selectedID = $this->ID;
    }

    //Start the select component
    $ret .= "<select value=0 id=\"" . $componentID . "\" name=\"" . $componentID . "\"";
    if ($isFormNavigation)
      $ret .= " onChange=\"document.getElementById('formSelect').submit();\"";
    else {  // If not navigation, is it used as a drop-down or a list ?
      if (null!==$size)
        $ret .= " size=" . $size;
      if ($multiple)
        $ret .= " multiple";
    }
    $ret .= ">";

    if ($isFormNavigation)
      $ret .="<option value=-1>Select a $this->DisplayName</option>";

    if ( !empty($rowdata))
    {
      foreach ($rowdata as $value)
      {
        $ret .= "<option value=" . $value['id'];
        if ($selectedID && $value['id']==$selectedID)
          $ret .= " selected";
        $ret .= ">" . $this->GetSelectOptionItemText($value) . "</option>";
      }

      $ret .= "</select>";
    }
    return $ret;

  }
  /* Show an HTML form, ready to post to $this->Classname (User, StorageItem, ...)
  */
  public function ShowFormNavigationSelect()
  {
    // TODO: these UI items are way overdue for being moved out,
    //      they do not need to be directly on the object

    $accessToken = PWH_SessionManager::GetParameter("accessToken");

    $ret = "<div style='display:inline;' class='resourceNav'><form style='display:inline;' class='none' id=\"formSelect\" action=\"?accessToken=$accessToken\" method=\"POST\">";
    $ret .= $this->ShowSelectRecordNavigation();
    $ret .= "&nbsp;<input type=\"submit\" name=\"add\" value=\"create\">";
    $ret .= "&nbsp;<input type=\"submit\" name=\"delete\" value=\"delete\">";
    $ret .= "</form>";
    $ret .= "&nbsp;&nbsp;&nbsp;<button onclick=\"{
      var selectedID = $('#ID :selected').val();\n

      var url = 'Report/" . $this->Classname .
        "/' + selectedID + '?accessToken=" . $accessToken . "';\n

        location.href=url;
          }\">Reports</button>";
    $ret .= "</div>";
    return $ret;
  }
  /*
     Show an HTML SELECT component that an be used as a field on a a form
     @param $selectedID - ID in the rendered list to set as $selected
     @param $componentName - Name of the component name of the HTML element
     @param $rowData - optional, ['id']['value to show']
  */
  public function ShowSelectDropDownComponent($selectedID, $rowData=null, $componentName=null, $size=null, $multiple=false)
  {
    return $this->buildSelectCombo(false, $rowData, 0, $selectedID, $componentName, $size, $multiple);
  }
  /*
     Show an HTML Listbox component that an be used as a field on a a form
     @param $selectedID - ID in the rendered list to set as $selected
     @param $foreignFieldName - Name of the
  */
  public function ShowSelectListBoxComponent($selectedID, $foreignFieldName, $foreignKeyValue, $size=1,$multiple=false, $rowData=null)
  {
    return $this->buildSelectCombo(false, $rowData, 0, $selectedID, $foreignFieldName, $size, $multiple);
  }
  /*
    GetSelectOptionItemText
     - Given an db results array, built a select optin line item
  *
  */
  public function GetSelectOptionItemText($record)
  {
    return "Undefined: " . $record['id'];
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
    Show form components and data, one on each row
  */
  public function showFormRecordFields()
  {

    foreach ($this->DB_Labels as $fieldName=>$fieldDef)
    {
      echo "<tr>";
      echo "<td><label for='{$fieldName}'>{$fieldDef["formlabel"]}</label></td>";
      switch($fieldDef["formcomponent"])
      {
        case "textarea":
          echo "<td align=left><textarea rows='4' cols='50' id='{$fieldName}' name='{$fieldName}'>{$this->GetField($fieldName)}</textarea></td>";
          break;
        case "text":

          echo "<td align=left><input type='{$fieldDef["formcomponent"]}' id='{$fieldName}' name='{$fieldName}' value='{$this->GetField($fieldName)}'></td>";
          break;
        case "select":
          echo "<td align=left>";

          $selectedExernalID = $this->GetField($fieldName);

          // This form has a component that is populated with data from another table.  Call that objects function
          $linkedResourceName = explode(".",$fieldDef["linkedFieldKey"])[0];//User.Id would evalulate to a user object
          $linkedRecord = new $linkedResourceName();//Users[1], Storagefacility[100]

          //Show a drop-down selectable list for this resource, using the current $fieldName as the HTML element id
          $rowData = PWH_SessionManager::GetOwnedRecordIDs($linkedResourceName);
        //  echo("count for $linkedResourceName" . count($rowData));
          //$rowData = null;
          echo $linkedRecord->ShowSelectDropDownComponent($selectedExernalID, $rowData, $fieldName);

          echo "</td>";
          break;
        case "list":
          echo "<td align=left>";
          // This form has a component that is populated with data from another table.  Call that objects function
          $linkedResourceName = explode(".",$fieldDef["linkedFieldKey"])[0];
          $linkedRecord = new $linkedResourceName();//i.e. Users[1], Storagefacility[100]
          echo $linkedRecord->ShowSelectListBoxComponent($this->GetField($fieldName), $fieldName, 10, true);
          echo "</td>";
          break;

      }
      echo "</tr>";
    }
  }
  /*
    Show linked and associative table objects
    These are items that this object is related to in two different ways:
      1.) Linked Collection - These are things that another object has this one as an owner
      2.) Associative Collections - Things that are in an associative table (when there are 1:N type of relations)

    Having two collections allows for an ultimate owner, which might be a user or another object.
    At the same time, the association table allows multiple relations, which can be used in any way the application needs.

    // NOTE: this is different than 'ownership' of a resource, which is used
        in the security and authorization checks.  They can actually refer to the
        same field, but do not have to.
  */

  function showFormdependentCollections()
  {
    if (!empty($this->DependentResources) || !empty($this->Associations))
    {


      // ===============================================
      // ============ SHOW THE DEPENDENT OBJECTS  ======
      // ===============================================
      if (!empty($this->DependentResourceData))
      {
        echo "<tr><td colspan=2><table>";
        foreach ($this->DependentResources as $dependentResourceName=>$dependencyResourceItem)
        {
            //Only populate dependent items that already exist.
            //  Adding items as dependencies is done on that item's form

            $dependentResourceObject =
                isset($this->DependentResourceData[$dependentResourceName]) ?
                $this->DependentResourceData[$dependentResourceName] : null;
            if ($dependentResourceObject)
            {
              echo "<tr>";

              echo "<td style='background-color: Darkgray; color:Black; padding:3px;'>
                    <label for='{$dependentResourceName}'>
                      <B>{$dependencyResourceItem['formlabel']}</B>
                    </label></td>";

              echo "<td align=left>";

              $elementID = $this->Name . $dependentResourceName;

              echo $this->showRecordsAsList(
                                        $elementID,
                                        $dependentResourceObject,
                                        $dependencyResourceItem["ListSize"]);


              echo "</td>";

              echo "</tr>";

            }
            else{
              error_log("There were no dependent resources for this record yet.");
            }
        }
        echo "</table></td></tr>";

      }
      // ===============================================
      // ============ SHOW THE ASSOCIATIONS ============
      // ===============================================
      if (!empty($this->Associations))
      {
          //Get the association records
          foreach ($this->AssociationData as $associativeCollectionName => $associationCollectionItem) {

            $foreignResources                 = $associationCollectionItem["ForeignResources"];
            $associativeTablePrimaryFieldName = $associationCollectionItem["associativeTablePrimaryFieldName"];

            foreach ($foreignResources as $foreignResourceName=>$associationObject) {

              $listSize             = $foreignResources[$foreignResourceName]["ListSize"];
              $foreignResourceLabel = $foreignResources[$foreignResourceName]["ForeignResourceLabel"];
              $linkedfieldName      = $foreignResources[$foreignResourceName]["LinkedFieldName"];

              $foreignResource      = new $foreignResourceName(); //create an object to pass to nav-bar

              $linkedResources          = $foreignResources[$foreignResourceName]["LinkedResources"];

              echo "<tr id='association-$associativeCollectionName'>";
              echo "<td style='background-color: #f2b21b; color:Black; padding:3px;'><B>$foreignResourceLabel</B></td>";


              // Show these related items
              echo "<td align=left>";


              //What type of navbar should be built?  It be populated from some API request,
              //  which is filtered by what the user has access to

              $navbarData = array("associativeCollectionName"         => $associativeCollectionName,
                                  "foreignFieldName"                  => $linkedfieldName,
                                  "foreignResourceName"               => $foreignResourceName,
                                  "associativeTablePrimaryFieldName"  => $associationCollectionItem["associativeTablePrimaryFieldName"],
                                  "rowData"                           => null
                                );

              $elementNameBase = $associativeCollectionName . $foreignResourceName;


              //// NOTE: THIS IS AN ORDERED ARRAY
              $navRowData = array(  $elementNameBase,
                                    $this->Classname,
                                    $this->ID,
                                    $associativeCollectionName,
                                    $foreignResourceName,
                                    $associativeTablePrimaryFieldName,
                                    $linkedfieldName
                                  );

              //Build the nav-bar
              $navbar = $this->buildNavbar(Warehouse\Constants\UI_NavigationTypes::NAVBAR_RECORD_ASSOCIATION,
                                      $elementNameBase,
                                      $foreignResource,
                                      $navbarData,
                                      $navRowData
                                    );


              //Push configs onto UI
              //// TODO: move this into more central place, get these scripts out of here !
              echo "<br>
                      <script>
                              PWH_UIService.ResourceConfig.Add('$this->Classname');
                              PWH_UIService.ResourceConfig.Items.$this->Classname.AddAssociation(
                                                  '$associativeCollectionName',
                                                  '$foreignResourceName',
                                                  '$associativeTablePrimaryFieldName',
                                                  '$linkedfieldName'
                                );
                      </script>";


              echo $foreignResource->showRecordsAsList($elementNameBase,
                                        $linkedResources,
                                        $listSize,
                                        $navbar,
                                        "GetRecordItemDisassociateLink",
                                        $navRowData);

                            echo "</td>";
                            echo "</tr>";

            }
          }

      }
    }
  }

  /*
   Using a prepared DB statement, return one or more objects using current called class
  */
  static function GetInstancesUsingQuery($preparedStatementName, $preparedStatementValues){

    $resourceName = get_called_class();
    $resources = [];
    $records = DataProvider::GET($preparedStatementName, $preparedStatementValues);

    if ( !empty($records))
    {
      foreach ($records as $rowData)
      {
        $resources[] = $resourceName::CreateFromRecord($rowData);
      }
    }
    return $resources;
  }
  /*
  Build and return HTML element for a nav-bar
  @param: $navbarData - Associative array, keys vary on $navbarType,
                      stored on DOM element
  */
  function buildNavbar($navbarType, $elementID, $resource, $navbarData=null){

    $selectElementName = $elementID . 'SELECT';
    $resourceName = $this->Classname;
    $resourceID = $this->ID;
    $resourceAction=null;
    $resourceActionString=null;

    switch ($navbarType) {
      case Warehouse\Constants\UI_NavigationTypes::NAVBAR_RECORD_NAV:

        break;
      case Warehouse\Constants\UI_NavigationTypes::NAVBAR_RECORD_LINKED:

        break;
      case Warehouse\Constants\UI_NavigationTypes::NAVBAR_RECORD_ASSOCIATION:


        $retHTML = "<form id='". $elementID . "NAV'>";
        $retHTML .= $resource->ShowSelectDropDownComponent(0, $navbarData['rowData'], $selectElementName);

        $retHTML .= "<button id='" . $elementID . "Submit'>add</button>";

        $retHTML .= "</form>";

        $resourceAction = "associate";

        //facilityowners/user
        $resourceActionItem = $navbarData['associativeCollectionName'] . "/" .
                              strtolower($navbarData['foreignResourceName']);



        $resourceActionString = "?" . $navbarData['foreignFieldName'] . "=";


        break;
      case Warehouse\Constants\UI_NavigationTypes::NAVBAR_RECORD_DISASSOCIATION:
        $retHTML = "NOT USED NOT USED";
        //// NOTE: NAVBAR IS NOT USED for the subnav, it is instead in UIManager::GetRecordItemDisassociateLink


        break;
      default:
        Log::error("Error with type for buildNavbar");

        break;
      }


      //TODO better/cleaner way to do this
      // ----- TODO TODO

      // --- ATTACH HANDLER FOR THIS NAVBAR ---

      // JQUERY - Attach a click handler to the nav submit just created
      ////api/resource/resourceid/resourceAction/resourceActionItem/actionitem2?FieldData

      if (1)
      {
          $associativeCollectionName = $navbarData['associativeCollectionName'];
          $foreignResourceName        = $navbarData['foreignResourceName'];
          $foreignFieldName           = $navbarData['foreignFieldName'];
          $elementIDList = $elementID . 'LIST';
          $elementIDNav = $elementID . 'NAV';

          //BUILD THE API URL = start with host/api/resource/resourceID

          //// TODO: why doesn't the default selected items yield zero via jquery?

          $retHTML .=
            "<script>
            $('#" . $elementID . "Submit').click(
              function(){
                var selectedID = $('#" . $selectElementName . " :selected').val();

                if (isNaN(selectedID))
                  selectedID = 1;
                \n
                var apiURL = '" . API_URL . "/" . strtolower($resourceName) . "/$resourceID";

                //ADD an action to the resource  /associate/
                if(!empty($resourceAction)){
                  $retHTML .= "/$resourceAction";
                }
                //ADD an ActionItem /associate/facilityowners/user
                if(!empty($resourceActionItem)){
                  $retHTML .= "/$resourceActionItem";
                }

                //ADD the QueryString /associate/facilityowners/user?userid={#}
                if(!empty($resourceActionString)){
                  $retHTML .= $resourceActionString . "' + selectedID;";
                }

          $retHTML .= "\n
                var callbackOrderedData = ['$elementID','$this->Classname',$this->ID,'$associativeCollectionName', '$foreignResourceName','$foreignFieldName', selectedID];
                var callbackMethodName = 'CloudServiceResponseHandlers.associate';
                CloudService.PUT('$elementID',
                                  apiURL,
                                  callbackMethodName,
                                  callbackOrderedData);

                return false;

              });
              </script>";
        }


      return $retHTML;

  }
  /*
    Associate this record to another record in the DB associated table configuerd
    in the associativeCollectionName
    @param: $associativeCollectionName - Name of the Associative Collection in config
    @param: $fieldData - Data needed for the query (i.e. userid:2)
  */
  function associate($associativeCollectionName, $foreignResourceName, $fieldData){
    $resourceName = get_called_class();
    $resources = [];
    $records = DataProvider::ASSOCIATE($this, $associativeCollectionName, ucfirst($foreignResourceName), $fieldData);
  }
  /*
    Associate this record to another record in the DB associated table configuerd
    in the associativeCollectionName
    @param: $associativeCollectionName - Name of the Associative Collection in config
    @param: $fieldData - Data needed for the query (i.e. userid:2)
  */
  function disassociate($associativeCollectionName, $foreignResourceName, $fieldData){
    $resourceName = get_called_class();
    $resources = [];
    $records = DataProvider::DISASSOCIATE($this, $associativeCollectionName, ucfirst($foreignResourceName), $fieldData);
  }
}
