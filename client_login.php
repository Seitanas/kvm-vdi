<?php
include ("functions/config.php");
require_once("functions/functions.php");
slash_vars();
$username=$_POST['username'];
$password=$_POST['password'];
$sql_reply=get_SQL_line("SELECT id,password FROM clients WHERE username LIKE '$username'");
if (hash_equals($sql_reply[1], crypt($password, $sql_reply[1]))) {
   //echo "Password verified!";
    session_start();
    $_SESSION['client_logged']='yes';
    $_SESSION['userid']=$sql_reply[0];
    $_SESSION['username']=$username;
    $_SESSION['ll']="gg";
    $ip = $_SERVER['REMOTE_ADDR'];
    add_SQL_line("UPDATE clients SET lastlogin=now(), ip='$ip' WHERE id='$sql_reply[0]'");
    header("Location: $serviceurl/client_pools.php");
    exit;
}
else {
    header("Location: $serviceurl/client_index.php?error=1");
    exit;
}
?>