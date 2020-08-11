<?php

?>
<div class='footer'>Public Warehouse Management Software 2020<p>
  <?php
    $accessToken = PWH_SessionManager::GetParameter('accessToken');
    $authCode = PWH_SessionManager::GetParameter('accessToken');
    $expiresUnixTime = PWH_SessionManager::GetParameter('expires_unix_time');

    $minutesRemaining = Util::GetFormattedMinutesRemaining($expiresUnixTime);

    if (!empty($accessToken) && $minutesRemaining>0){
      echo "Session [" . $accessToken . "]";
      if (!empty($minutesRemaining))
      {
        $minutesRemaining = $minutesRemaining<1 ? "less than 1 minute" : "$minutesRemaining minutes";
        echo " - autologout: $minutesRemaining  ";
      }
    }
    else if (!empty($authCode)){
      echo "Authorization [" . $authCode . "]";
    }

  ?><br>
  <a target=_blank href='https://github.com/goability/publicwarehouse'>Project Repository @github</a>
</div>
<!-- INCLUDE Scripts at the bottom to allow UI to load smoothly first -->

<script type="text/javascript">
  PWH_UIService.API_URL = 'http://localhost:8888/api';
</script>
</body>
</html>
