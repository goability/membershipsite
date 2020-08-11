<?php

if (is_null($this)){
  echo "No record ";
  Log::error("Resource was null");
  exit();
}
?>

<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link active" data-toggle="tab" href="#manage">Profile</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#clients">Clients</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" data-toggle="tab" href="#actions">Actions</a>
  </li>

</ul>

<div class="tab-content">
  <div class="tab-pane container fade" id="actions">Do some things</div>
  <div class="tab-pane container fade" id="clients">List of clients</div>
  <div class="tab-pane container active" id="manage">
        <?php
         include("formItemRecordGeneric.php");?>
  </div>
</div>
