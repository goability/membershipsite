<?php
/*
  Determine the route and resource
  Call get_parameters()
  Setup Context object that can be referenced in form
  Include Resource
*/
function handle_route()
{
  //Extract Routing parameters from URL and place in array
  // Standard format:  /Resource/ID/ResourceAction/ResourceActionItem
  // TODO: currently get_parameters spits out common names, with no knowledge of route
  //   this causes problem below with each case pulling out strangly named params

  $route_params = Util::get_parameters();

  $resource   = $route_params["resource"];
  $resourceID = $route_params["resourceID"];
  $formMode   = $route_params["formProperties"]["MODE"];
  $formData   = (isset($_POST)) ? $_POST : null;
  $showForm   = false;
  $activeReport = "";// TODO: default this to a favorite/last, etc

  //this is the default view to show when no sessioning is valid
  $viewToInclude = "LoginView.php";

  $accessTokenRequired = true;//set to false on signup route

  //Grab any authCodes or AccessTokens from Get or Post, these are compared against any session tokens
  if (!SecurityManager::SetTokens()){
    Log::error("SECURE FAILURE while trying to get tokens from post or get");
    echo "APPLICATION SECURITY FAILURE WITH TOKENS";
    return;
  }
  $accessToken  = SecurityManager::$AccessToken;
  $authCode     = SecurityManager::$AuthCode;

  //echo "AccessToken is $accessToken " . strlen($accessToken);

  if (strpos($accessToken, "\""))
  {
    echo "  --- double";
  }

  PWH_SessionManager::ResumeSession();// TODO: should be using auth_start

  // Load configurations from SessionManager, which will call ConfigurationManager as needed
  ConfigurationManager::GetConfigurationObject()->ResourceNames;


  //Menu is shown now and will include the sessioning tokens
  include "MenuTop.php";

  //Setup the data for the record that will be shown
  // default - should be used for most cases
  // $class - Creates new class based on resourceName (User, Storagefacility, ...)

  //Things not requiring a login
  switch($resource)
  {

    case 'Forgot': //Show the forgot password form

      $d = getcwd() . DIRECTORY_SEPARATOR .
                      "forms" .
                      DIRECTORY_SEPARATOR .
                      "formForgotPassword.php";

      if (file_exists($d)){
        include ($d);
      }
      return;

      break;
    case 'Reset': //Show the Reset Password form

      //User is NOT logged in, but they have an accessToken.
      //  UserID is NOT passed on the URL, so request it from DB
      //  It will be set as hidden parameter on form
      $accessTokenData = DataProvider::GET_ACCESS_TOKEN_DATA($accessToken);

      if (empty($accessTokenData))
      {
        Log::error("Access token not provided or found");
        return;
      }
      else{
        $userID = $accessTokenData["userID"];

        $d = getcwd() . DIRECTORY_SEPARATOR .
                        "forms" .
                        DIRECTORY_SEPARATOR .
                        "formPasswordReset.php";

        if (file_exists($d)){
          include ($d);
        }
      }
      return;
      break;
    case 'Logout': //Just expire the sessions and redirect home
      DataProvider::DELETE_AUTHORIZATIONS(PWH_SessionManager::GetCurrentUserID());
      Util::DestroySiteSession();


      break;
    case 'Login':
        session_destroy();
        //If an AuthCode is being passed, we are in Login Flow STEP 2
                // 1.) Request an accessToken
                // 2.) Start Session
                // 3.) Set location to home page
                // TODO: allow setting of desired homepage, and session timeout
        if (isset($authCode))
        {
          // TODO: Don't pass userID on URL , move to POST
          $userID = isset($_GET['userID']) ? $_GET['userID'] : null;

          if (!SecurityManager::ValidateAuthCode($authCode, $userID)){
            Log::error("LOGIN - authCode invalid");
          }
          else {

            //Load all of the configurations
            ConfigurationManager::LoadAllResourceConfigs();

            $c = ConfigurationManager::GetLoadedResourceCount();
            Log::debug("ROUTER -- Resources are now loaded - count: $c");

            //Now request an accessToken using the userID and auth
            $accessTokenData = DataProvider::SET_ACCESS_TOKEN($userID, $authCode, TOKEN_TTL_SESSION_AUTOLOGOUT);
            $accessToken      = $accessTokenData["accessToken"];
            $expiresUnixTime  = $accessTokenData["expires_unix_time"];

            //Create a resource for this user
            $userRecord = new User($userID);

            // Start the session and set some data that should be good for the session
            PWH_SessionManager::StartSession($userID, $accessToken, $expiresUnixTime);

            // TODO: IT is fine to set these parameters because they wont change,
            //        but be careful when linking data that can chagne into session

            PWH_SessionManager::SetParameter('isAdministrator', $userRecord->IsAdministrator);
            PWH_SessionManager::SetParameter('currentEmailAddress', $userRecord->DB_Fields["emailaddress"]);
            PWH_SessionManager::SetParameter('currentUsername', $userRecord->DB_Fields["profilename"]);

            Log::debug("ROUTER -- SETTING CURRENT ACL Setting into SESSION for user $userRecord->ID is admin: $userRecord->IsAdministrator");

            //Will ask the DataProvider to build and return the ACL list for this user
            // TODO: If a resource is added while login, it needs to be added to this list
            $resourceAccess = DataProvider::GET_ACCESSIBLE_RESOURCES($userRecord);

            //var_dump($resourceAccess);
            foreach ($resourceAccess as $key => $value) {
              //echo "<Br>Resource: " . $key;
              //if ($key==="Provider"){echo $value["ACL"][32][0][0];}
            }

            PWH_SessionManager::SetParameter(\Warehouse\Constants\SessionVariableNames::RESOURCE_ACL, $resourceAccess);

            // LOGIN COMPLETE, FORWARD TO THE DASHBOARD
           echo "<script>window.location.assign('" . SITE_URL . "/Dashboard?accessToken=$accessToken');</script>";

          }

        }
        else{  //AuthCode was not passed, clean everything up if there are old $sessions
              //  If a /Login route is requested on site, user is logged out !
              Util::DestroySiteSession();
        }

        break;
        // END OF LOGIN
    case 'Signup':
      $viewToInclude = "SignupView.php";
      $accessTokenRequired = false;
      break;

    default://These things require user to be logged in

      if (PWH_SessionManager::IsActive($accessToken)){
        $userID = PWH_SessionManager::GetCurrentUserID();;

        Log::info("==== SESSION IS Active .. Requested Resource: $resource");
        switch($resource)
        {
          case 'Dashboard': //// TODO: this should be simply /, meaning default
            $viewToInclude = "DashboardView.php";
            break;
          case 'Inventory':
            $viewToInclude = "InventoryView.php";
            break;
          case 'Report':
            // Using standard format, reporting params are shifted:
            //

            //   /Resource  /resourceID /ResourceAction  /ResourceActionItem
            //   /Report    /User       /0               /ReportName

            //Create a resource object that has reports available for:
            //  Resource Type - At the Type level - all users, ...
            //        :: /Reports/User/0
            //  Resource Record -  At row level - Associations/Linked - Locations@Facility
            //        :: /Reports/User/1

            // To avoid confusion, let's rename this now

            $resourceClassName = $resourceID;

            if (class_exists($resourceClassName)){
              $recordID = 0; //always default to 0, which will give type-level
              if (array_key_exists("resourceAction", $route_params)){
                $recordID = $route_params["resourceAction"] > 0 ? $route_params["resourceAction"] : 0;
              }
              if (array_key_exists("resourceActionItemData", $route_params) &&
                  !empty($route_params["resourceActionItemData"])
                ){
                $activeReport = $route_params["resourceActionItemData"][0];
                // NOTE:  URL path params 3 and after are packaged into this array
              }
              Log::debug("Creating resource $resourceClassName with recordID $recordID");
              //Set current resource - could be 0 or a real record
              $currentResource = new $resourceClassName($recordID);
            }
            //Now include the report form, which will reference the $currentResource
            $viewToInclude = "ReportView.php";
            break;
          default: // DYNAMIC RESOURCE - Getting a record
          // ====== GETTING A RECORD USING $resourceID passed in

            //Handle User resource for non-admins

            if (  $resource==="User" && $userID!=$resourceID &&
                  !PWH_SessionManager::IsAdministrator()
                )
            {
              Log::error("SECURITY - This user is not an admin,
                            but they requested another user OR CREATE NEW User record!!
                            Should not have happened.  Forcing back to this user id in READ mode");
              $resourceID = PWH_SessionManager::GetCurrentUserID();
              $formMode = "READ";
            }

            $viewToInclude = null;
            $resourceID = ($resourceID>0) ? $resourceID : 0;
            if (class_exists($resource)){
              $showForm = true;
              $class =  $resource;
              $currentRecord = new $class($resourceID);//Users[1], Storagefacility[100]
            }
            break;
        }
      }

  }//end of switch

  // TODO: this is floating, needs refactoring

  if (  !$accessTokenRequired ||
        ($accessTokenRequired && PWH_SessionManager::IsActive($accessToken))
      ){

    if (!empty($viewToInclude)){
      include $viewToInclude;
    }

    // When a request comes in, the mode of the form is passed from the previous
    //  request.  If it is empty, it is assumed that the form is in create mode
    //Handle form CRUD requests
    switch($formMode)
    {
      case "CREATE":
        $currentRecord->InsertRecord($formData);

        //Now that record is inserted, reset record forcing form to imap_clearflag_full
        $currentRecord = $currentRecord->GetNewInstance();//setup this form ready to insert another one
        $route_params["formProperties"]["MODE"] = "CREATE";

        break;

      case "READ":
        $currentRecord->GET($ID);
        break;

      case "UPDATE":

        $currentRecord->UpdateRecord($formData);
        //After update, this record will remain loaded

        break;
      case "DELETE":
        $currentRecord->DeleteRecord($resourceID);
        //setup this form ready to insert another one
        $currentRecord = $currentRecord->GetNewInstance();
        $route_params["formProperties"]["MODE"] = "CREATE";
        break;

      default:

        //set mode for form below
        if ( isset($resourceID) && $resourceID>0 ){
          $route_params["formProperties"]["MODE"] = "UPDATE";
        }
        else if ($showForm){
          Log::debug("Mode was default.  setting form mode to create");
          $route_params["formProperties"]["MODE"] = "CREATE";
        }
        break;
    }//end CRUD SWITCH

    //SHOW THE RECORD FORM
    if ($showForm)
    {
      $currentRecord->SetFormProperties($route_params["formProperties"]);

      switch($resource)
      {
        //This was refactored to support one form that is built from the json config
        default:
          include "DefaultView.php";
      }
    }
  }
  else{
    if (!is_null($accessToken)){
      $loginMessage = "Session expired, please login again.";
      Util::DestroySiteSession();
      $accessToken = $authCode = null;
    }
    include "LoginView.php";
  }
}
