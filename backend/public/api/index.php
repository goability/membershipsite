<?php

/*
Warehouse API

/resource/{id}/{command}?{params}

COMMANDS:  ASSOCIATE
  -- This is used when a record is requesting association with another record,
      which ultimate requires entry into an Associative table:
          storagefacilityowners = {user.id, facility.id}


*/
$currentIncludePath = get_include_path();

//set_include_path($currentIncludePath . PATH_SEPARATOR . "../src/classes");

require_once "../../src/classes/includesAPI.php";

//CREATE THE LOG INSTANCE
Log::init('API');

Log::debug('========== INCOMING API REQUEST =========');

//echo $HTTP_CODE[403];
// Parse the URL

$resource = "";
$reourceID = 0;

//// TODO:
//TODO PUT SECURE BACK IN FOR THE REST API !!
/*
if (!Util::verify_request()){
  $errorCode = 403;
  $message = "PWH Error";
  $reply = Util::build_error_reply($errorCode, $message);
  header($reply);
}*/


//TODO note - copied code from menuAdminRouter, todo refactor this
$requestType  = $_SERVER['REQUEST_METHOD'];

$route_params = Util::get_parameters();
$resource     = $route_params["resource"];

$resourceID   = empty($route_params["resourceID"]) ? 0 : $route_params["resourceID"];
$resourceName = trim($resource);// substr(ucwords($resource),0,-1);//stripo off the plural

$resourceAction = $route_params["resourceAction"]; //  /user/1/associate/{associationTablename}/{foreignRecordID}

//Look for tokens in GET and Post, validate, and set them

SecurityManager::SetTokens();
$accessToken  = SecurityManager::$AccessToken;
$authCode     = SecurityManager::$AuthCode;

//Handle non-resource requests:  Authenticate, Signup

Log::debug("Loading it for api");
ConfigurationManager::LoadStaticConfigurations();

if (!class_exists($resourceName)){

  $error = true;
  $errMsg = "";

  switch ($resourceName) {

    case 'Authenticate'://Auth only returns a userId

      //Get the username and password from the post data

      //Send into dataprovider to see if there is a record matching uname and hash
      $userID = DataProvider::AUTHENTICATE($_POST['username'], $_POST['password']) > 0 ? true : false;

      //Return UserID on success, 401 on failure
      if ($userID > 0) {
        $error = false;
        echo $userID;
      }
      else{
        // NOTE: never tell why an auth fails
        $errMsg = "Authentication failure";
      }

      break;
    case 'Login': //will authenticate AND create a new session

      $userLoginData = DataProvider::LOGIN($_POST['username'], $_POST['password']);

      if (null!=$userLoginData){
        Log::info("API::Login - Sending back userid and temp auth code");
        $error = false;
        echo $userLoginData[0] . ',' . $userLoginData[1];
      }
      else {
        $errMsg = "Login failure";
        Log::error("login failure");
      }
      break;

    case 'Signup':

      $emailaddress = $_POST['EmailAddress'];
      $username     = $_POST['Username'];
      $password     = $_POST['Password'];
      $firstname    = $_POST['Firstname'];
      $lastname     = $_POST['Lastname'];
      $city         = $_POST['City'];
      $state        = $_POST['State'];
      $zip          = $_POST['Zipcode'];

      $results      = DataProvider::ADD_USER( $emailaddress, $username, $password, $firstname,
                                      $lastname, $city, $state, $zip);


      $userID       = $results[0];
      $errMsg       = $results[1];

      if ($userID>0){
        $error = false;
        echo $userID;

        //Now add any requested associations
/*
        $primaryType      = "provider";
        $foreignType      = "client";
        $primaryRecordID  = 0;
        $foreignRecordID  = 0;

        DataProvider::AddAssociationRequest(  $userID,
                                              $primaryType,
                                              $primaryRecordID,
                                              $foreignType,
                                              $foreignRecordID);
  */
      }
      break;
    case 'Reset': //Reset a Password
        //RESET has two steps, driven by $_POST['resetStep']
        //  1.) request -  request a link to be sent, this data comes in from form and includes an already
        //        generated and ticking authCode
        //  2.) reset  - Actually change the password

        $resetStep = isset($_POST["resetStep"]) ? $_POST["resetStep"] : null;
        Log::info("RESETING A PASSWORD");
        /// ResetStep is Mandatory.  If not present, transaction fails
        if (is_null($resetStep)){
          $errMsg = "RESET STEP EMPTY";
          Log::error("SECURITY - Password RESET Bad request - resetStep '$resetStep'! ");
          return null;
        }
        else{

          switch($resetStep)
          {

            case 'request':
              //validate authcode AND emailaddress together

              //ERROR is ALWAYS FALSE FOR A REQUEST:
              //   This is on purpose to avoid letting a bot know if emailaddress actually existed
              $error = false;

              $emailaddress = isset($_POST["emailaddress"]) ? $_POST["emailaddress"] : null;
              if  (   !SecurityManager::ValidateAuthCode($authCode) ||
                      is_null($emailaddress)
                  ){
                  Log::error("SECURITY error with authCode or emailaddress input - '$emailAddress'");
              }
              else {


                $userID = DataProvider::VERIFY_USER_EXISTS($emailaddress, null, $authCode);
                if ($userID > 0){
                  //Found a userID, grant them a very short lived AccessToken
                  //  Send email with clickable link - ONLY with the AccessToken
                  // The router will verify the request, generate a new Accesstoken and build the reset form

                  $accessTokenData = DataProvider::SET_ACCESS_TOKEN($userID, $authCode, TOKEN_TTL_PASSWORD_RESET);

                  $accessToken = $accessTokenData["accessToken"];
                  $expiresUnixTime = $accessTokenData["expires_unix_time"];

                  $resetLink = SITE_URL . "/Reset?accessToken=$accessToken";
                  Log::info("API::Reset - Sending generated password reset to user $emailaddress - $resetLink");
                  $expiryFormatted = Util::GetFormattedDate($expiresUnixTime);
                  Log::info("The access token will expire at $expiryFormatted");
                  $message = EmailService::GeneratePasswordResetMessage($resetLink);

                  //EmailService::SendMail($emailaddress, "PASSWORD RESET", $message);

                  Log::debug($message);

                  }
                  else{
                    Log::error("RESET REQUEST - Failed because $emailaddress did not exist in the system.");
                  }

                }
                break;
            case 'reset':
              Log::info("Access token $accessToken");


              //Reset the password
              $passwordRaw  = isset($_POST['password_raw']) ? $_POST['password_raw'] : null;
              $userID       = isset($_POST['userid']) ? $_POST['userid'] : null;
              $accessToken  = isset($_POST['accessToken']) ? $_POST['accessToken'] : null;

              if  (   !SecurityManager::ValidateAccessToken($accessToken, $userID) ||
                      is_null($passwordRaw) || is_null($userID)
                  ){
                  Log::info("SECURITY error with userid or password");
              }
              else {
                Log::info("ALMOST THERE RESETTNIG IT NOW");
                  $error = DataProvider::RESET_PASSWORD($userID, $accessToken, $passwordRaw);

                  if(!$error){
                    Log::info("Password reset success for user $userID");
                  }
                  else{
                    $errMsg = "Password Reset failure";
                    Log::error($errMsg);
                  }
              }
              Log::info("REMOVING ALL authorizations for user $userID");
              //ALWAYS REMOVE all authtokens for this userID, force a new flow
              DataProvider::DELETE_AUTHORIZATIONS($userID);

              break;
            default:
              Log::error("LOGIC ERROR with Unknown Reset Password step $resetStep");

            }
          }

      break;

  default:

    $errMsg = "Resource not found " . $resourceName;
    break;
  }


// TODO: USE 401 Header.  Had to revert this because jquery POST reply does
//         not give the error or fail cases as expected or I dont know how to do it yet

  if ($error){
      //$reply = Util::build_error_reply(401, $errMsg);
      //header($reply);
      echo "0, $errMsg";
  }
}
else{
//CLASS EXISTS THIS IS A RESOURCE REQUEST

  Log::info("Incoming $requestType request for resource $resourceName . ID=$resourceID");
  //Create the database connection
  try
  {
    //ResourceID is always required unless POSTING (creating)
    if ($resourceID==0 && $requestType!="POST"){
      $errorCode = 400;
      $message = "Malformed Request";
      $reply = Util::build_error_reply($errorCode, $message);
      header($reply);
      echo $message;
      exit();
    }

    Log::debug("======= LOADING RESOURCE $resourceName");
    $currentRecord = new $resourceName($resourceID);

    if ($currentRecord == null || ($requestType!="POST" && $currentRecord->ID==0))
    {
      //This should never happen
      $errorMsg = "Resource not found " . $resourceName;
      $reply = Util::build_error_reply(401, $errorMsg);
      header($reply);
      exit();
    }
    else
    {

      Log::debug("========  HERE   =========");
      $post_vars = [];

      //var_dump($post_vars)
      if ($requestType=="PUT" || $requestType=="DELETE"){
        //https://lornajane.net/posts/2008/accessing-incoming-put-data-from-php
        parse_str(file_get_contents("php://input"),$post_vars);

        switch ($resourceAction)
        {
          case "associate":
          case "disassociate":
            $fieldData                  = $route_params['queryParameters'];
            $associativeCollectionName  = $route_params['resourceActionItemData'][0];
            $foreignResourceName        = $route_params['resourceActionItemData'][1];
            break;
          default:
            break;
        }
      }
      switch ($requestType) {
        case "POST":
          //get the $_POST data and pass it to CREATE

          break;
        case "GET":
          echo $currentRecord->toJSON();
          break;

        break;
        case "PUT":
          /* Puts are used for updates to the record AND associations
          /api/storagefacility/3/associate/facilityowners/user?userid=4
          */
          switch ($resourceAction)
          {
            case "associate":
              $currentRecord->associate($associativeCollectionName, $foreignResourceName, $fieldData);
              echo reset($fieldData);//// TODO: PASSING THIS RIGHT BACK BAD DESIGN ?
              break;
            default:
              break;
          }
        //get the $_POST data and pass it to UPDATE

        break;
        case "DELETE":
          //grab the ID and pass it to delete
          ///api/storagefacility/3/disassociate/facilityowners/user?userid=4

          switch ($resourceAction)
          {
            case "disassociate":
              $currentRecord->disassociate($associativeCollectionName, $foreignResourceName, $fieldData);
              echo reset($fieldData);//// TODO: PASSING THIS RIGHT BACK BAD DESIGN ?
              break;
            default:
              break;
          }

          break;

        default:
          // code...
          break;
      }
    }

  }
  catch (Exception $e)
  {
    Log::error(str($e));
  }
}



function build_reply($msg){
  echo $msg;
}
/*
buid an error reply
*/
function build_error_reply($msg){

      Log::error($errorMsg);
      $reply = Util::build_error_reply(401, $errorMsg);

}


?>
