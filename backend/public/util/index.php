<?php

 //Use this file to run tests


 require_once "../classes/User.php";

 // Scaffolding examples:
 // Show main Navigation at top
 // Show content:
 //     Show User Form
 // Show footer at bottom
 //   Show User form

 $currentRecord = new User(1);
 $currentRecord->ShowForm();
