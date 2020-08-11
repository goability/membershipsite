<?php

?>
<div class="card">
<div class="container">
  <div class="row">
    <div class="col-6">
      <img class="card-img-top"
      src="<?php echo('/images/resources/' . $resourceImageHeader);?> "
      alt="Card image cap">
    </div>
    <div class="col-6"
          style="overflow: hidden;
                white-space: nowrap;" >

        <h5 class="card-title"><?php echo $displayText; ?></h5>
        <p class="card-text">
          <p class="card-text">
            <span class="badge badge-success">Available <b>21</b></span><br>
            <span class="badge badge-warning">Pending <b>4</b></span><br>
            <span class="badge badge-secondary">Loaded <b>1074</b></span><br>
          </p>
        </p>
        <p class="card-text"><small class="text-muted">Locations</small></p>

    </div>
  </div>
</div>
</div>
