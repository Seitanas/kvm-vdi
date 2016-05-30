<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$password1=$_POST['password1'];
$password2=$_POST['password2'];
if (!empty($password1)&&!empty($password2)){
    $id=$_SESSION['userid'];
    $cryptpw=crypt($password1);
    add_SQL_line("UPDATE users SET password='$cryptpw' WHERE id='$id'");
 }
header("Location: $serviceurl/reload_vm_info.php");
exit;
?>
