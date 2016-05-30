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
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vm=$_GET['vm'];
$hypervisor=$_GET['hypervisor'];
$action=$_GET['action'];
if (empty($vm)||empty($hypervisor)){
    exit;
}
$h_reply=get_SQL_line("SELECT * FROM hypervisors WHERE id='$hypervisor'");
ssh_connect($h_reply[2].":".$h_reply[3]);
if ($action=="mass_on" || $action == "mass_off" || $action == "mass_destroy"){
    $child_vms=get_SQL_array("SELECT name FROM vms WHERE source_volume='$vm'");
    $x=0;
    while ($child_vms[$x]['name']){
	if ($action=="mass_on")
	    ssh_command("sudo virsh start " . $child_vms[$x]['name'],true);
        if ($action=="mass_off")
    	    ssh_command("sudo virsh shutdown " . $child_vms[$x]['name'], true);
        if ($action=="mass_destroy")
    	    ssh_command("sudo virsh destroy " . $child_vms[$x]['name'], true);
        ++$x;
    }
}
if ($action=="single"){
    $v_reply=get_SQL_line("SELECT name FROM vms WHERE id='$vm'");
    $state=$_GET['state'];
    if ($state=="up")
	ssh_command("sudo virsh start " . $v_reply[0], true);
    if ($state=="down")
	ssh_command("sudo virsh shutdown " . $v_reply[0], true);
    if ($state=="destroy")
	ssh_command("sudo virsh destroy " . $v_reply[0], true);

}
header("Location: $serviceurl/reload_vm_info.php");
exit;
?>
