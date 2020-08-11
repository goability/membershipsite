<?php
/*
$hash = password_hash("mattc1234", PASSWORD_DEFAULT);

echo "\n" . $hash . "\n";

$password = "mattc1234";

echo "\nVerify results: ";

echo password_verify($password, $hash);

echo "\n";*/
/*
require_once "Mail.php";

$from = "Sandra Sender <sender@example.com>";
$to = "Ramona Recipient <recipient@example.com>";
$subject = "Hi!";
$body = "Hi,\n\nHow are you?";

$host = "ssl://mail.example.com";
$port = "465";
$username = "smtp_username";
$password = "smtp_password";

$headers = array ('From' => $from,
  'To' => $to,
  'Subject' => $subject);
$smtp = Mail::factory('smtp',
  array ('host' => $host,
    'port' => $port,
    'auth' => true,
    'username' => $username,
    'password' => $password));

$mail = $smtp->send($to, $headers, $body);

if (PEAR::isError($mail)) {
  echo("<p>" . $mail->getMessage() . "</p>");
 } else {
  echo("<p>Message successfully sent!</p>");
}*/

$ar = array();
$ar[] =

$ar[] = 1;
$ar[] = "*";

if (in_array(1,$ar)){
  echo("yes");
}
if (in_array("*",$ar)){
  echo("yes");
}
