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
$action='';
if (isset($_GET['vm']))
    $vm=$_GET['vm'];
$hypervisor=$_GET['hypervisor'];
if (isset($_GET['action']))
    $action=$_GET['action'];
if (isset($_GET['parent']))
    $parent=$_GET['parent'];
if (empty($hypervisor)){
    exit;
}
if (empty($vm)&&empty($parent)){
    exit;
}
if ($action!='mass_delete'){
    $h_reply=get_SQL_array("SELECT * FROM hypervisors WHERE id='$hypervisor'");
    ssh_connect($h_reply[0]['ip'] . ":" . $h_reply[0]['port']);
    $v_reply=get_SQL_array("SELECT id,name,machine_type FROM vms WHERE id='$vm'");
    if ($v_reply[0]['machine_type']=='initialmachine'){//we need to delete child VMs if machine is initial
	$child_vms=get_SQL_array("SELECT id,name FROM vms WHERE source_volume='{$v_reply[0]['id']}'");
	$x=0;
	while ($x<sizeof($child_vms)){
	    $source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $child_vms[$x]['name'] . "|grep vda| awk '{print $2}' ",true)));
	    if (empty ($source_path))
    		$source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $child_vms[$x]['name'] . "|grep hda| awk '{print $2}' ",true)));
	    if (empty ($source_path)||$source_path=='-'||strtolower(substr($source_path, -4))=='.iso')//if we have cd drive, then disk image would be second drive
		$source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $child_vms[$x]['name'] . "|grep hdb| awk '{print $2}' ",true)));
	    if (!empty($source_path))
		write_log(ssh_command("sudo rm " . $source_path, true));
	    write_log(ssh_command("sudo virsh destroy " . $child_vms[$x]['name'], true));
	    write_log(ssh_command("sudo virsh undefine " . $child_vms[$x]['name'], true));
	    add_SQL_line("DELETE FROM vms WHERE id='{$child_vms[$x]['id']}' LIMIT 1");
	    ++$x;
	}
    }
    $source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $v_reply[0]['name'] . "|grep vda| awk '{print $2}' ",true)));
    if (empty ($source_path))
        $source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $v_reply[0]['name'] . "|grep hda| awk '{print $2}' ",true)));
    if (empty ($source_path)||$source_path=='-'||strtolower(substr($source_path, -4))=='.iso')//if we have cd drive, then disk image would be second drive
	$source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $v_reply[0]['name'] . "|grep hdb| awk '{print $2}' ",true)));
    if (!empty($source_path))
	write_log(ssh_command("sudo rm " . $source_path, true));
    write_log(ssh_command("sudo virsh destroy " . $v_reply[0]['name'], true));
    write_log(ssh_command("sudo virsh undefine " . $v_reply[0]['name'], true));
    add_SQL_line("DELETE FROM vms WHERE id='$vm' LIMIT 1");
}
if ($action=='mass_delete'){
    $v_reply=get_SQL_array("SELECT * FROM vms WHERE source_volume='$parent'");
    $h_reply=get_SQL_array("SELECT * FROM hypervisors WHERE id='$hypervisor'");
    ssh_connect($h_reply[0]['ip'] . ":" . $h_reply[0]['port']);
    $x=0;
    while ($x<sizeof($v_reply)){
	$source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $v_reply[$x]['name'] . "|grep vda| awk '{print $2}' ",true)));
	if (empty ($source_path))
    	    $source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $v_reply[$x]['name'] . "|grep hda| awk '{print $2}' ",true)));
	if (empty ($source_path)||$source_path=='-'||strtolower(substr($source_path, -4))=='.iso')//if we have cd drive, then disk image would be second drive
	    $source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $v_reply[$x]['name'] . "|grep hdb| awk '{print $2}' ",true)));
	if (!empty($source_path)){
	    ssh_command("sudo virsh destroy " . $v_reply[$x]['name'], true);
	    ssh_command("sudo virsh undefine " . $v_reply[$x]['name'], true);
	    ssh_command("sudo rm " . $source_path, true);
	}
	add_SQL_line("DELETE FROM vms WHERE id='{$v_reply[$x]['id']}' LIMIT 1");
        ++$x;
    }
}
header("Location: $serviceurl/reload_vm_info.php");
exit;
?>
