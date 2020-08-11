<?php
/*
An Email service for sending emails
*/
class EmailService{

  public static $FromNoReply = EMAIL_FROM_USER_NOREPLY;
  public static $FromDomain = EMAIL_FROM_DOMAIN;

  /*
    Send an email from no-reply@configuredDomain
  */
  public static function SendMail($to, $subject, $message){

    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    $sender = self::$FromNoReply . "@" . self::$FromDomain;
    // More headers
    $headers .= 'From: ';
    $headers .= "<$sender>" . "\r\n";

    Log::info("PASSWORD RESET - Sending link to $to");

    mail($to,$subject,$message,$headers);
  }

  public static function GeneratePasswordResetMessage($url){

    $message = "
    <html lang='en'>
      <head>
        <meta charset='utf-8'>
        <title></title>
      </head>
      <body>
        A password reset has been requested.  It will expire in under five minutes.
        <br>Please ignore this request if this was not requested.<br><br>
        <a href=$url>CLICK HERE</a> to reset your password.
      </body>
    </html>";

    return $message;
  }
}
?>
