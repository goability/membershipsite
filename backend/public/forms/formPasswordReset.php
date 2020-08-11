<?php
/*
Show password reset form.
 - Form is ONLY shown after validation of the AuthCode, which was set when the
     email was sent out and added to the URL

On button click:
  - Verify passwords match
  -  Submit to password reset URL API via cloudService
  -   Handle Result: Forward window.location to /Login

*/

$apiURL = API_URL . "/Reset";

echo "Access Token is: $accessToken";
echo "<br>UserID is: $userID";
?>

<form id="reset-password-form" class="form-horizontal"
  onsubmit="PWH_UIService.ChangePassword('<?php echo $accessToken; ?>')"
  method="post">
  <fieldset>
    <legend>Reset Password</legend>
    <div class="form-group">
      <label class="col-sm-4" for="password">Password</label>
      <div class="col-sm-6">
        <input type="password" id="password" class="form-control">
      </div>
    </div>
    <div class="form-group">
      <label class="col-sm-4" for="vpassword">Password (again)</label>
      <div class="col-sm-6">
        <input type="password" id="vpassword" class="form-control">
        <input type="hidden" name="resetStep" value="reset" id="resetStep">
        <input type="hidden" name="accessToken" value="<?php echo $accessToken;?>" id="accessToken">
        <input type="hidden" name="userID" value="<?php echo $userID;?>" id="userID">
      </div>
    </div>
    <div class="form-group">
      <div class="col-sm-12">
        <button id="submit" type="submit" disabled="true">Reset Password</button>
      </div>
    </div>
  </fieldset>
</form>
<script type="text/javascript">
  $("#password").focusout(PWH_UIService.validatePassword);
  $("#vpassword").focusout(PWH_UIService.validatePassword);
</script>
