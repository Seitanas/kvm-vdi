<?php
include ("functions/config.php");
require_once("functions/functions.php");
slash_vars();
$mysql_conn=SQL_connect();
$username=mysqli_real_escape_string($mysql_conn,$_POST['username']);
$password=mysqli_real_escape_string($mysql_conn,$_POST['password']);
$sql_reply=mysqli_fetch_row(mysqli_query($mysql_conn, "SELECT id,password FROM users WHERE username LIKE '$username'"));
mysqli_close($mysql_conn);
if (password_verify($password, $sql_reply[1])){
    if (session_status() == PHP_SESSION_NONE)
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