<?php
$topNavMenuItems = ConfigurationManager::GetLoadedResourceNavConfigItems(PWH_SessionManager::GetAccessibleResourceNames());

//Not showing User on the dashboard

unset($topNavMenuItems["User"]);
?>
<div class="container">
  <div class="row">

      <div class="card-deck">
    <?php

    foreach ($topNavMenuItems as $menuItemResource => $menuItemConfig) {

        $menuItemResourceName = $menuItemConfig["resourceName"];
        $menuItemURL          = $menuItemConfig["url"];
        $displayText          = $menuItemConfig["displayText"];
        $resourceImageHeader  = $menuItemConfig["resourceImageLarge"];

        $resourceSummaryCard  = "config/resources/summaryCard_Generic.php";
        $f = "config/resources/summaryCard_$menuItemResourceName.php";
        if(file_exists($f)){
          $resourceSummaryCard  = "config/resources/summaryCard_$menuItemResourceName.php";
        }
        $resourceURL = SITE_URL . $menuItemURL . "?accessToken=$accessToken";
    ?>

        <div class="col-6" onclick="window.location='<?php echo $resourceURL;?>'">
          <?php include($resourceSummaryCard);?>
        </div>
      <?php } ?>
    </div>

  </div>
</div>
