<?php
include ('../../../functions/config.php');
require_once('../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$vm = $_POST['vm'];
$hypervisor = $_POST['hypervisor'];
if (empty($vm) || empty($hypervisor)){
    echo json_encode(array('error' => _('Missing values.')));
    exit;
}
$h_reply = get_SQL_line("SELECT * FROM hypervisors WHERE id = '$hypervisor'");
$v_reply = get_SQL_line("SELECT * FROM vms WHERE id = '$vm'");
ssh_connect($h_reply[2].":".$h_reply[3]);
add_SQL_line("UPDATE vms SET maintenance = 'true' WHERE source_volume = '$vm'");
add_SQL_line("UPDATE vms SET snapshot = 'false' WHERE source_volume = '$vm'");
$source_path = str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $v_reply[1] . "|grep vda| awk '{print $2}' ",true)));
if (empty ($source_path))
    $source_path = str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $v_reply[1] . "|grep hda| awk '{print $2}' ",true)));
if (empty ($source_path) || $source_path=='-'||strtolower(substr($source_path, -4))=='.iso')//if we have cd drive, then disk image would be second drive
    $source_path = str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $v_reply[1] . "|grep hdb| awk '{print $2}' ",true)));
#destroy all runing child vms
$child_vms = get_SQL_array("SELECT name FROM vms WHERE source_volume='$vm'");
$x=0;
while ($x < sizeof($child_vms)){
    ssh_command("sudo virsh destroy " . $child_vms[$x]['name'], true);
    $dest_path = str_replace("\n", "",(ssh_command("sudo virsh domblklist " . $child_vms[$x]['name'] . "|grep vda| awk '{print $2}' ",true)));
    ssh_command("sudo qemu-img create -f qcow2 -b $source_path $dest_path",true);
    ssh_command("sudo virsh start " . $child_vms[$x]['name'],true);
    ++$x;
}
echo json_encode(array('success' => _('Machines populated successfully')));
exit;
?>
