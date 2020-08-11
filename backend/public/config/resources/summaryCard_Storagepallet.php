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
            <span class="badge badge-success">Available <b>187</b></span><br>
            <span class="badge badge-warning">Pending <b>0</b></span><br>
            <span class="badge badge-secondary">Loaded <b>3311</b></span><br>
          </p>
        </p>
        <p class="card-text"><small class="text-muted">Pallets</small></p>

    </div>
  </div>
</div>
</div>
