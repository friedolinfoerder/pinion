<?php

include 'uninstall.php';

// redirect to cms
session_start();
$_SESSION["refresh"] = true;
header('Location: ./');


?>
