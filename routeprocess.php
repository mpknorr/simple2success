<?php

if(!isset($_SESSION)){
    session_start();
}

// All users → index.php first (orientation/welcome page).
// From there they navigate to start.php themselves.
if(isset($_SESSION["userid"])){
    require_once "includes/conn.php";
}

header("Location: " . $baseurl . "/backoffice/index.php");
exit();
