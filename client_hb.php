<?php
/*
KVM-VDI
Tadas Ustinavičius
2017-12-12
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_client_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
if (!empty($_POST['vmname']))
    $vmname = $_POST['vmname'];
if (!empty($_POST['vm_id']))
    $vm_id = $_POST['vm_id'];
if (empty($vmname) && empty($vm_id))
    exit;
$userid = $_SESSION['userid'];
if($engine == 'OpenStack' && !empty($vm_id)) // thin client sends vm_id = osInstanceId
    $v_reply = getSQLarray("SELECT * FROM vms WHERE osInstanceId='$vm_id'");
else if ($engine == 'OpenStack' && !empty($vmname)) // html5 client sends vmname = osInstanceName
    $v_reply = getSQLarray("SELECT * FROM vms WHERE osInstanceName='$vmname'");
else // native KVM-VDI routine
    $v_reply = get_SQL_array("SELECT * FROM vms WHERE name='$vmname'");
if ($v_reply[0]['clientid'] != $userid)// allow only clients, which were given current VM to modify heartbeats
    exit;
if($engine == 'OpenStack')
    add_SQL_line("UPDATE vms SET lastused=NOW() WHERE osInstanceId='{$v_reply[0]['osInstanceId']}'");
else
    add_SQL_line("UPDATE vms SET lastused=NOW() WHERE name='$vmname'");
