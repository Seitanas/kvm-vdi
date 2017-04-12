<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$vm_name = $_POST['vm_name'];
$vm_type = $_POST['vm_type'];
$os_type = $_POST['os_type'];
$flavor = $_POST['flavor'];
$volume_id = $_POST['volume_id'];
$networks = $_POST['networks'];
$source_vm=$_POST['source_vm'];
$volume_size = $_POST['volume_size'];

if (!empty($vm_name) && !empty($vm_type) && !empty($os_type) && !empty($flavor) && !empty($volume_id) && !empty($networks) && !empty($source_vm)){
    $network_array = array();
    foreach($networks as $uuid)
        array_push($network_array, array('uuid' => $uuid));
    $delete_on_termination = true;
    $maintenance = 'false';
    if ($vm_type == 'vdimachine'){ // we need to leave VDI VM image if VM is deleted (for rebuild purposes)
        $delete_on_termination = false;
        $maintenance = 'true';
    }
    if ($vm_type == 'ephemeralvdi')
        $vm_name = $vm_name . '-ephemeralvdi';
    if ($vm_type == 'sourcemachine')
        $reply = createVM($vm_name, $vm_type, $flavor, $volume_id, $network_array, $delete_on_termination, 'image', $volume_size);
    else
        $reply = createVM($vm_name, $vm_type, $flavor, $volume_id, $network_array, $delete_on_termination, 'volume', 0);
    $result = json_decode($reply, TRUE);
    if ($result['server']['id']){
        $osInstanceNetworks = json_encode($network_array);
        $source_vm = getSQLArray("SELECT id FROM vms WHERE osInstanceId='$source_vm'");
        add_SQL_line("INSERT INTO vms (name, machine_type, source_volume, maintenance, state, os_type, osInstanceId, osInstanceMasterVolume, osInstanceNetworks) VALUES ('$vm_name', '$vm_type', '{$source_vm[0]['id']}', '$maintenance', 'building', '$os_type', '{$result['server']['id']}', '$volume_id', '$osInstanceNetworks')");
        $vmArray = getSQLArray("SELECT vms.id, vms.name, vms.machine_type, vms.source_volume, vms.snapshot, vms.maintenance, vms.filecopy, vms.state,
         vms.spice_password, vms.clientid, vms.lastused, vms.os_type, vms.locked, vms.osHypervisorName, vms.osInstanceName, vms.osInstanceId, vms2.name
         AS source_volume_machine FROM `vms` vms left JOIN vms vms2 ON vms.source_volume=vms2.id WHERE vms.osInstanceId='{$result['server']['id']}' ORDER BY vms.name");
        echo json_encode($vmArray[0]);
    }
    else echo $reply;
}
else
    echo json_encode(array('error' => 'missing-values'));