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
                white-space: wrap;" >

        <h5 class="card-title"><?php echo $displayText; ?></h5>
        <p class="card-text">

            <span class="badge badge-success">Active Storage</span> <b>3521</b><br>
            <span class="badge badge-warning">Recent Shipped</span> <b>12</b><br>
            <span class="badge badge-secondary">Requests Pending</span> <b>20</b><br>
            <br>
            <button type="button" class="btn btn-info btn-sm">SHIP</button><Br>
              <button type="button" class="btn btn-warning btn-sm">STORE</button>
        </p>
        <p class="card-text"><small class="text-muted">You recently shipped 12 items on July 15, 2020</small></p>

    </div>
  </div>
</div>
</div>
