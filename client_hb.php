<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt
2016-05-30
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_client_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vmname=$_POST['vmname'];
if (empty($vmname)){
    exit;
}
$userid=$_SESSION['userid'];
$v_reply=get_SQL_array("SELECT * FROM vms WHERE name='$vmname'");
if ($v_reply[0]['clientid']!=$userid)//allow only clients, which were given current VM to modify heartbeats
    exit;

add_SQL_line("UPDATE vms SET lastused=NOW() WHERE name='$vmname'");
exit;
?>
