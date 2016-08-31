<?php
/*
KVM-VDI
Tadas Ustinavičius
tadas at ring.lt
2016-08-31
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
    $child_vms=get_SQL_array("SELECT name,os_type FROM vms WHERE source_volume='$vm'");
    $x=0;
    while ($child_vms[$x]['name']){
	if ($action=="mass_on"){
	$agent_command=json_encode(array('vmname' => $child_vms[$x]['name'], 'username' => '', 'password' => '', 'os_type' => $child_vms[$x]['os_type']));
        ssh_command('echo "' . addslashes($agent_command) . '"| socat /usr/local/VDI/kvm-vdi.sock - ',true);
	//ssh_command("sudo virsh start " . $child_vms[$x]['name'],true);
	}
        if ($action=="mass_off")
    	    ssh_command("sudo virsh shutdown " . $child_vms[$x]['name'], true);
        if ($action=="mass_destroy")
    	    ssh_command("sudo virsh destroy " . $child_vms[$x]['name'], true);
        ++$x;
    }
}
if ($action=="single"){
    $v_reply=get_SQL_array("SELECT id,name,os_type,machine_type FROM vms WHERE id='$vm'");
    $state=$_GET['state'];
    if ($state=="up"){
	if ($v_reply[0]['machine_type']=='initialmachine'){//if we are powering initial machine up, we should power down all child VMs and put them to maintenance mode
	    $child_vms=get_SQL_array("SELECT name,os_type FROM vms WHERE source_volume='{$v_reply[0]['id']}' AND state<>'shut'");
	    $x=0;
	    while ($x<sizeof($child_vms)){
		write_log(ssh_command("sudo virsh destroy " . $child_vms[$x]['name'], true));
		++$x;
	    }
	    add_SQL_line("UPDATE vms SET maintenance='true' WHERE source_volume='{$v_reply[0]['id']}'");
	}
	$agent_command=json_encode(array('vmname' => $v_reply[0]['name'], 'username' => '', 'password' => '', 'os_type' => $v_reply[0]['os_type']));
        ssh_command('echo "' . addslashes($agent_command) . '"| socat /usr/local/VDI/kvm-vdi.sock - ',true);
	//ssh_command("sudo virsh start " . $v_reply[0], true);
    }
    if ($state=="down")
	ssh_command("sudo virsh shutdown " . $v_reply[0]['name'], true);
    if ($state=="destroy")
	ssh_command("sudo virsh destroy " . $v_reply[0]['name'], true);

}
header("Location: $serviceurl/reload_vm_info.php");
exit;
?>
