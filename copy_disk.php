<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vm=$_GET['vm'];
$hypervisor=$_GET['hypervisor'];
if (empty($vm)||empty($hypervisor)){
    exit;
}
$h_reply=get_SQL_line("SELECT * FROM hypervisors WHERE id='$hypervisor'");
$v_reply=get_SQL_line("SELECT * FROM vms WHERE id='$vm'");
$source_reply=get_SQL_line("SELECT name FROM vms WHERE id='$v_reply[4]'");
ssh_connect($h_reply[2].":".$h_reply[3]);
$source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist $source_reply[0]|grep vda| awk '{print $2}' ",true)));
if (empty ($source_path))
    $source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist $source_reply[0]|grep hda| awk '{print $2}' ",true)));
if (empty ($source_path)||$source_path=='-'||strtolower(substr($source_path, -4))=='.iso')//if we have cd drive, then disk image would be second drive
    $source_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist $source_reply[0]|grep hdb| awk '{print $2}' ",true)));
$dest_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist $v_reply[1]|grep vda| awk '{print $2}' ",true)));
if (empty ($dest_path))
    $dest_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist $v_reply[1]|grep hda| awk '{print $2}' ",true)));
if (empty ($dest_path)||$dest_path=='-'||strtolower(substr($dest_path, -4))=='.iso')//if we have cd drive, then disk image would be second drive
    $dest_path=str_replace("\n", "",(ssh_command("sudo virsh domblklist $v_reply[1]|grep hdb| awk '{print $2}' ",true)));
#$filekey= uniqid();
add_SQL_line("UPDATE vms SET filecopy='0' WHERE id='$vm'");
add_SQL_line("UPDATE vms SET maintenance='true' WHERE source_volume='$vm'");
#destroy all runing child vms
$child_vms=get_SQL_array("SELECT name FROM vms WHERE source_volume='$vm'");
$x=0;
while ($x<sizeof($child_vms)){
    ssh_command("sudo virsh destroy " . $child_vms[$x]['name'], true);
    ++$x;
}
ssh_command("sudo " . $hypervisor_cmdline_path . "copy-file $source_path $dest_path $vm",false);
header("Location: $serviceurl/dashboard.php");
exit;
?>
