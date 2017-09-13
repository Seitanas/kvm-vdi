<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => _('Please login first')));
    exit;
}
slash_vars();
$vm = $_GET['vm'];
$hypervisor = $_GET['hypervisor'];
if (empty($vm) || empty($hypervisor)){
    echo json_encode(array('error' => _('Missing values.')));
    exit;
}
$h_reply = get_SQL_line("SELECT * FROM hypervisors WHERE id='$hypervisor'");
$v_reply = get_SQL_line("SELECT * FROM vms WHERE id='$vm'");
$source_reply = get_SQL_line("SELECT name FROM vms WHERE id='$v_reply[4]'");
ssh_connect($h_reply[2] . ":" . $h_reply[3]);
$source_file = str_replace("\n", "", (ssh_command("sudo virsh domblklist $source_reply[0]|grep vda| awk '{print $2}' ",true)));
if (empty($source_file))
    $source_file = str_replace("\n", "", (ssh_command("sudo virsh domblklist $source_reply[0]|grep hda| awk '{print $2}' ",true)));
if (empty($source_file) || $source_file == '-' || strtolower(substr($source_file, -4)) == '.iso')//if we have cd drive, then disk image would be second drive
    $source_file = str_replace("\n", "",(ssh_command("sudo virsh domblklist $source_reply[0]|grep hdb| awk '{print $2}' ",true)));
$destination_file = str_replace("\n", "",(ssh_command("sudo virsh domblklist $v_reply[1]|grep vda| awk '{print $2}' ",true)));
if (empty ($destination_file))
    $destination_file = str_replace("\n", "",(ssh_command("sudo virsh domblklist $v_reply[1]|grep hda| awk '{print $2}' ",true)));
if (empty ($destination_file) || $destination_file == '-' || strtolower(substr($destination_file, -4)) == '.iso')//if we have cd drive, then disk image would be second drive
    $destination_file = str_replace("\n", "",(ssh_command("sudo virsh domblklist $v_reply[1]|grep hdb| awk '{print $2}' ",true)));
add_SQL_line("UPDATE vms SET filecopy = '0' WHERE id='$vm'");
add_SQL_line("UPDATE vms SET maintenance = 'true' WHERE source_volume = '$vm'");
$child_vms = get_SQL_array("SELECT name FROM vms WHERE source_volume = '$vm'");
$x = 0;
while ($x < sizeof($child_vms)){
    ssh_command("sudo virsh destroy " . $child_vms[$x]['name'], true);
    ++$x;
}
$agent_command=json_encode(array('command' => 'COPYDISK', 'vm' => $vm, 'source_file' => $source_file, 'destination_file' => $destination_file));
ssh_command('echo "' . addslashes($agent_command) . '"| socat /usr/local/VDI/kvm-vdi.sock - ',true);
echo json_encode(array('success' => _('Created successfully')));
