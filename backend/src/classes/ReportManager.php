<?php

/*
*/
/**
 *
 */
class ReportManager
{

  function __construct()
  {
    // code...
  }
  /*
  @param: $reportType - Warehouse\Constants\ReportType::
  */
  static function showReportNavbar($resource, $recordID, $reportType){

    $accessToken = PWH_SessionManager::GetParameter("accessToken");
    echo "<ul class=\"nav nav-pills\">";
    foreach ($resource->ReportConfig[$reportType] as $reportName=>$reportConfig) {

      echo "<li class=\"nav-item\">
            <a class=\"nav-link active\" href=\"/Report/$resource->Name/$recordID/$reportName?accessToken=$accessToken\">$reportName</a>
          </li>";
      }
      echo "</ul>";
  }
  /*
  @param: $associationCollection - array[name][foreignResourcesName] = displayText

  */
  static function showRecordAssociations($associationCollection){
    echo "<ul>";
    foreach ($associationCollection as $associativeCollectionName=>$foreignResources) {
      echo "<li><b>$associativeCollectionName</b></li>";
      echo "<ul>";
      foreach ($foreignResources as $foreignResourceName=>$associatedRecords){
        echo "<li>$foreignResourceName</li>";
        echo "<ul>";
        foreach ($associatedRecords as $displayText) {
            echo "<li>$displayText</li>";
        }
        echo "</ul>";
      }
      echo "</ul>";
    }
    echo "</ul>";
  }
}
