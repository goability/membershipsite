<?php
/*
General Utility functions
*/
require_once "Constants.php";
require_once "Log.php";

class Util
{

  /*  Extract input parameters from $_POST, $_GET, and $URL
    // API: api/storagefacility/3/associate/facilityowners/user?userid=2
    // SITE: /Storagefacility/3
    // REPORTING: /Reports/Storagefacility?{minid;maxid;sortfield;sortorder;recordids}
    $Resource - User, Facility, ...
    $ResourceID - One specific record  (Can come from path OR $POST.  If both exist, they must match (for security))
    @returns array["resource", "resourceID", "formProperties"['showCancel, 'Mode']]

  */
  public static function get_parameters()
  {
    Log::info("Getting the parameters");
    //Extract Resourece and ResoureceID (OPT) from URL
    $URI = explode('/', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));
    $resourceIndex = ($URI[1]=="api") ? 2 : 1;
    //Resource is ALWAYS obtained from the path
    $resource = $URI[$resourceIndex];

    Log::info("Resource: " . $resource);

    //Resource ID can come from path OR from $_POST form, the later takes priority
    $resourceID = (count($URI)>$resourceIndex+1) ? $URI[$resourceIndex+1] : null;

    $resourceAction = (count($URI)>$resourceIndex+2) ? $URI[$resourceIndex+2] : null;

    //Now package the remaining parameters into an array.  Each call will pull out orde
    $resourceActionItemData = (count($URI)>$resourceIndex+3) ? array($URI[$resourceIndex+3]) : null;

    if (count($URI)>$resourceIndex+4){
      $resourceActionItemData[] = $URI[$resourceIndex+4];
    }

    //If a form posted data, let's take a look
    if (isset($_POST))
    {
      $Mode = isset($_POST['MODE']) ? $_POST['MODE'] : null;
      //see if the resourceID came from a form
      $resourceID = (isset($_POST['ID'])) ? $_POST['ID'] : $resourceID;

      //No Mode was set, so this didn't come in from a data form
      if (!isset($Mode))
      {
        if ( isset($_POST["add"]))
        {
          $Mode = "ADDNEW";
          $resourceID = 0;
        }
        else if ( isset($_POST["delete"]))
        {
          $Mode = "DELETE";
        }
      }
    }


    // TODO, what other properties could be set on a form (AFTER the input has been scanned at this point)
    $formProperties = array("MODE"=>$Mode);

    parse_str($_SERVER['QUERY_STRING'], $queryParameters);

    return array("resource"=>$resource,
                  "resourceID"=>$resourceID,
                  "resourceAction"=>$resourceAction,
                  "resourceActionItemData"=>$resourceActionItemData,
                  "formProperties"=>$formProperties,
                  "queryParameters"=>$queryParameters);

  }
  /*
    Verify inbound request, extract headers.
    Must contain: Authorization, AccessToken
    returns bool success
  */
  public static function verify_request()
  {

    $valid = false;

    foreach ($_SERVER as $name => $value)
    {
       if (substr($name, 0, 5) == 'HTTP_')
       {
           $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
           $headers[$name] = $value;
       } else if ($name == "CONTENT_TYPE") {
           $headers["Content-Type"] = $value;
       } else if ($name == "CONTENT_LENGTH") {
           $headers["Content-Length"] = $value;
       }
    }

    if ($headers["Authorization"]){
      if ($headers["Accesstoken"]){
        $valid = true;
        Log::info("Authorization passes for remote client: " . $_SERVER["REMOTE_ADDR"]);
      }
      else{
        Log::error("Access token missing from header: " . $_SERVER["REMOTE_ADDR"]);
      }
    }
    else{
      Log::error("Authorization missing in header: "  . $_SERVER["REMOTE_ADDR"]);
      Log::error($headers);
    }
    return $valid;
  }
  /* Build an HTTP Error response
    @param httpCode - which code to send in reply, 404, 500, etc
    @param message - Text message to send in reply
  */
  public static function build_error_reply($httpCode, $message){
    return "HTTP/1.1 " . $httpCode . " " . HTTP_CODES[$httpCode] . " " . $message;

  }
  /*
    Convert an array into CSV.  Uses in-memory array
    https://stackoverflow.com/questions/7362322/get-return-value-of-fputcsv
    @param $ary - associative array [colName]=value
    @param $dbLabels - What is shown
  */
  public static function Array2csv($ary, $dbLabels, $addquotes=false)
  {
    if ($addquotes)
    {
      foreach ($ary as $key => $value) {
        try {
          //TODO - this was added to skip ID field, look at bette way
          if (key_exists($key, $dbLabels))
          {
            if ($dbLabels[$key]["dataType"]=="string")
            {
                $ary[$key] .= "@# "; //add this to force escaping by fputcsv below
            }
          }

        } catch (\Exception $e) {
          Log::error("ERROR: " . $e->getMessage());
        }
      }
    }
    $buffer = fopen('php://temp', 'r+');
    fputcsv($buffer, $ary);
    rewind($buffer);
    $csv = fgets($buffer);
    fclose($buffer);

    if ($addquotes)
      $csv = str_replace("@# ", "", $csv);

    return $csv;
  }
  public static function AddDebugMessage($msg)
  {
    if(DEBUG_MODE){
      echo "<hr><div class=debugStyles><b>DEBUGGING INFO</b><br>" . $msg . "</span>";
    }
  }

  public static function Authenticate($uname, $passHash){

    //Call DB with uname and password hash
  }
  public static function guidv4()
  {
      //https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
      $data = openssl_random_pseudo_bytes(16);

      assert(strlen($data) == 16);

      $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
      $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

      return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }
  public static function GenerateAuthorizationCode(){

    return Util::guidv4();

  }
  public static function GenerateAccessToken(){
    return Util::guidv4();
  }
  /*
  Get a Y-m-d H:i:s formmated date
  */
  public static function GetFormattedDate($unixTimeStamp){
    if (!is_nan($unixTimeStamp)){
      $date = new DateTime("@$unixTimeStamp");
      return $date->format('Y-m-d H:i:s');
    }
    else {
      return null;
    }
  }
  /*
  *  Return Minutes minutes remaining in string form
  */
  public static function GetFormattedMinutesRemaining($unixTimeEnd){

    if (!is_nan($unixTimeEnd)){
      return strval( ($unixTimeEnd - time())/60);
    }
    else {
      return "0";
    }
  }
  /*
    For any sessioning saved for a user:
      Delete the Database authorization entries
      Delete all sessions
  */
  public static function DestroySiteSession(){
    DataProvider::DELETE_AUTHORIZATIONS(PWH_SessionManager::GetCurrentUserID());
    PWH_SessionManager::EndSession();
  }
}
