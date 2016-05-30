<?php
include ("functions/config.php");
require_once("functions/functions.php");
slash_vars();
$username=$_POST['username'];
$password=$_POST['password'];
$sql_reply=get_SQL_line("SELECT id,password FROM users WHERE username LIKE '$username'");
if (hash_equals($sql_reply[1], crypt($password, $sql_reply[1]))) {
   //echo "Password verified!";
    session_start();
    $_SESSION['logged']='yes';
    $_SESSION['userid']=$sql_reply[0];
    $ip = $_SERVER['REMOTE_ADDR'];
    $data = date("Y.m.d H:i:s");
    add_SQL_line("UPDATE users SET lastlogin='$data', ip='$ip' WHERE id='$sql_reply[0]'");
    header("Location: $serviceurl/reload_vm_info.php");
    exit;
}
else {
    header("Location: $serviceurl/?error=1");
    exit;
}
?>