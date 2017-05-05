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
$volume_id = $_POST['volume_id'];
$source_vm = $_POST['source_vm'];
$target_vm = $_POST['target_vm'];
$source_vm_info = getVMInfo($source_vm);
$flavor = $source_vm_info['server']['flavor']['id'];
$source_vm_info = getSQLArray("SELECT * FROM vms WHERE osInstanceId = '$source_vm'");
$network_array = json_decode($source_vm_info[0]['osInstanceNetworks']);
if (!empty($vm_name) && !empty($vm_type) && !empty($volume_id) && !empty($source_vm)){
    $delete_on_termination = true;
    $reply = createVM($vm_name, $vm_type, $flavor, $volume_id, $network_array, $delete_on_termination, 'volume', 0);
    $result = json_decode($reply, TRUE);
    if ($result['server']['id']){
        $osInstanceNetworks = json_encode($network_array);
        if (!empty($target_vm))
            $target_vm_info = getSQLArray("SELECT osInstanceId FROM vms WHERE osInstanceId='$target_vm'");
        if (empty($target_vm_info[0]['osInstanceId']))//if this is first time ephemeral machine is created
            add_SQL_line("INSERT INTO vms (name, machine_type, source_volume, state, os_type, osInstanceId, osInstanceMasterVolume) VALUES ('$vm_name', '$vm_type', '{$source_vm_info[0]['id']}', 'building', '$os_type', '{$result['server']['id']}', '$volume_id')");
        else
            add_SQL_line("UPDATE vms SET clientid = '0', lastused = '0', osInstanceId = '{$result['server']['id']}' WHERE osInstanceId = '$target_vm'");
/*        $vmArray = getSQLArray("SELECT vms.id, vms.name, vms.machine_type, vms.source_volume, vms.snapshot, vms.maintenance, vms.filecopy, vms.state,
        vms.spice_password, vms.clientid, vms.lastused, vms.os_type, vms.locked, vms.osHypervisorName, vms.osInstanceName, vms.osInstanceId, vms2.name
        AS source_volume_machine FROM `vms` vms left JOIN vms vms2 ON vms.source_volume=vms2.id WHERE vms.osInstanceId='{$result['server']['id']}' ORDER BY vms.name");
        echo json_encode($vmArray[0]);
*/
        //json_encode(array('status' => 'success'));
    }
    echo $reply;

}
else
    echo json_encode(array('error' => 'missing-values'));