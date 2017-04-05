<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');
if (!check_session()){
    echo json_encode(array('error' => 'nologin'));
    exit;
}
slash_vars();
$vm_id = $_POST['vm_id'];
$console_type = $_POST['console_type'];
if (!empty ($vm_id) && !empty($console_type)){
    if ($console_type == 'spice'){
        $vm_array = get_SQL_array("SELECT vms.spice_password, vms.osInstancePort, vms.osInstanceId, hypervisors.ip  FROM vms LEFT JOIN hypervisors ON vms.osHypervisorName = hypervisors.name WHERE vms.osInstanceId='$vm_id'");
        $command = array();
        $command['command'] = 'make-spice-channel';
        $command['hypervisor_ip'] = $vm_array[0]['ip'];
        $command['spice_password'] = $vm_array[0]['spice_password'];
        $command['spice_port'] = $vm_array[0]['osInstancePort'];
        $command['vm_id'] = $vm_array[0]['osInstanceId'];
        $command = json_encode($command);
        $reply = sendToBroker($command);
        if (!$reply)
            echo json_encode(array('status' => 'socket-failed'));
        else{
            $reply = json_decode($reply, true);
            $reply['spice_address'] = $kvm_vdi_broker_spice_address;
            $reply['spice_password'] = $vm_array[0]['spice_password'];
            echo json_encode($reply);
            }
        exit;
    }
}
