<?php

if (is_null($this)){
  echo "No record ";
  Log::error("Resource was null");
  exit();
}
?>

<ul class="nav nav-tabs">
  <li class="nav-item ">
    <a class="nav-link active" data-toggle="tab" href="#manage">Manage</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#items">Stored Items</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#staged">Staged Items</a>
  </li>

</ul>

<div class="tab-content">
  <div class="tab-pane container fade" id="items">Currently stored items</div>
  <div class="tab-pane container fade" id="staged">Items waiting to be stored</div>
  <div class="tab-pane container active" id="manage">
        <?php
         include("formItemRecordGeneric.php");?>
  </div>
</div>
