<?php

require_once "../src/classes/includesSite.php";

//Show header
require("./views/HeaderView.php");

handle_route();

require("./views/FooterView.php");
