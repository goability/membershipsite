<?php
/*
Display a login form with Forgot username/password links
Include text region for message to show login failures/logged out, etc

*/

$apiURL = API_URL . "/Login";
$callbackMethodName = "CloudServiceResponseHandlers.login";

?>
<div class="container" id="loginContainer" style="display:inline;">
  <form class="">
    <div class="row" style="max-width:400;">
      <div class="col-2">Username</div>
      <div class="col-2"><input type="text" name="username" id="username"></div>
      <div class="w-100"></div>
      <div class="col-2">Password</div>
      <div class="col-2"><input type="password" name="password" id="password"></div>
    </div>
    <div class="row">
      <div class="col-2" style="font-size: smaller;"><a href="/Forgot">Forgot password</a></div>
      <div class="col-2" style="display:inline;">
        <button type="button" name="login" value="Login" onclick="PWH_UIService.loginUser('<?php echo $apiURL . '\',\'' . $callbackMethodName; ?>')">Login</button>
        &nbsp;<span style="font-size:smaller;color:Blue;" id="loginStatusMessage" name="loginStatusMessage">
          <?php if (isset($loginMessage)){ echo $loginMessage;}?><br><a href="/Signup">Create Account</a>
          </span>
      </div>
    </div>
  </form>
</div>
