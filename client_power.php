<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt
2017-04-24
Vilnius, Lithuania.
*/
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_client_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vm=$_POST['vm'];
$action=$_POST['action'];
if (empty($vm)||empty($action)){
    exit;
}
$userid=$_SESSION['userid'];
if ($engine == 'OpenStack')
    $v_reply = get_SQL_array("SELECT * FROM vms WHERE osInstanceId='$vm'");
else
    $v_reply = get_SQL_array("SELECT * FROM vms WHERE id='$vm'");
if ($v_reply[0]['clientid'] != $userid)//allow only clients, which were given current VM to change its power state
    exit;
if ($engine == 'OpenStack')
    echo vmPowerCycle($vm, $action);
else {
    $h_reply = get_SQL_array("SELECT * FROM hypervisors WHERE id='{$v_reply[0]['hypervisor']}'");
    ssh_connect($h_reply[0]['ip'].":".$h_reply[0]['port']);
    if ($action == "shutdown"){
        ssh_command("sudo virsh shutdown " . $v_reply[0]['name'], true);
    }
    if ($action == "destroy"){
        ssh_command("sudo virsh destroy " . $v_reply[0]['name'], true);
    }
}
reload_vm_info();
exit;
?>
