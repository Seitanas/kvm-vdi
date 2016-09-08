<?php
include ("functions/config.php");
require_once("functions/functions.php");
if (isset($_GET['type'])){
    if($_GET['type']=='client'){
    if (session_status() == PHP_SESSION_NONE)
	session_start();
    $_SESSION['client_logged']='';
    header("Location: $serviceurl/client_index.php");
    exit;
    }
}
else{
    close_session();
    header("Location: $serviceurl");
    exit;
}
?>