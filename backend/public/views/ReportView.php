<?php
/*
 Reports View - called from menuAdminRouter

 Vars defined in menuAdminRouter.php:
  $currentResource could be 'StorageFacility' OR the name field of the resource
  $recordID = 0 (resource type) or > 0 (if a record is loaded)


*/
if (empty($currentResource)){
  echo "To show a report, select the resource, then click reports";
}
else{
  $currentResourceName = $currentResource->Name;
//$currentResource->ID -- $currentResource->GetListItemText()<br>

  $reportType = ($recordID > 0) ?   Warehouse\Constants\ReportType::RECORD :
                                    Warehouse\Constants\ReportType::RESOURCE;

  //safety - Resources are not required to have record or resourc level reports
  //this will be prevented at higher level, but better to put here as well
  if (!array_key_exists($reportType, $currentResource->ReportConfig)){
    Log::error("A report was requested for type $reportType on resource $currentResourceName however that type did not exist");
    exit();
  }
  echo ReportManager::showReportNavbar($currentResource, $recordID, $reportType);


  if(!empty($activeReport)){
    Log::debug("Showing report $activeReport");
    $reportConfig = $currentResource->ReportConfig[$reportType][$activeReport];
    $rowHeaderItems = explode(",", $reportConfig["row-header"]);

    echo "<H1>$currentResourceName $activeReport</H1>";

    switch ($reportType) {
      case Warehouse\Constants\ReportType::RESOURCE:

      //TYPE 1 - Record Listings for a type, no recordID

      //Get the data

      $records = DataProvider::GET(Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_GREATER . $currentResourceName::$Tablename, [0]);

      foreach ($records as $record) {
        //`var_dump($record);

          echo "<b>";
          foreach ($rowHeaderItems as $headerItem) {
            echo $record[$headerItem];
          };

          echo "</b><Br>";
        /*
          foreach ($reportConfig["row"] as $rowItem) {
            echo $rowItem["col-head"];
          }*/
          foreach ($reportConfig["row-detail"] as $rowItem) {
            echo $record[$rowItem["col-data"]] . " ";
          }

          echo "<br>";
        }
        break;
      case Warehouse\Constants\ReportType::RECORD:

    //$record = DataProvider::GET(Warehouse\Constants\SqlPrepareTypes::SQL_SELECT_GREATER . $currentResourceName::$Tablename, [0]);
      if (array_key_exists("associativeCollectionName", $currentResource->ReportConfig[$reportType][$activeReport])) {
        $associativeCollectionName = $currentResource->ReportConfig[$reportType][$activeReport]["associativeCollectionName"];

      }
      $associationData = DataProvider::GetAssociatedRecords($currentResource);

      foreach ($associationData as $associativeCollectionName => $associationCollectionItem) {
        $foreignResources = $associationCollectionItem["ForeignResources"];
        foreach ($foreignResources as $foreignResourceName=>$associationObject) {

          $listSize             = $foreignResources[$foreignResourceName]["ListSize"];
          $foreignResourceLabel = $foreignResources[$foreignResourceName]["ForeignResourceLabel"];
          $linkedfieldName      = $foreignResources[$foreignResourceName]["LinkedFieldName"];

          $foreignResource      = new $foreignResourceName(); //create an object to pass to nav-bar

          $linkedResources          = $foreignResources[$foreignResourceName]["LinkedResources"];

          echo "<br><b>" . $foreignResourceLabel . "</b>";

          foreach ($linkedResources as $resourceItem) {
            echo "<br>&nbsp;&nbsp;&nbsp;" . $resourceItem->GetListItemText();
            // code...
          }
        }

      }


        break;

      default:
        Log::error("Design error - bad $reportType passed in.");
        break;
    }





  }
  //var_dump($currentResource->ReportConfig);

  //cho "<br>" . $currentResource->Name;
}
