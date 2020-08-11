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
          <span class="badge badge-warning">New requests <b>4</b></span>
        </p>
        <p class="card-text"><small class="text-muted">You have three providers<br>There are 4 requests for service</small></p>

    </div>
  </div>
</div>
</div>
