<?php
include dirname(__FILE__) . '/../../../functions/config.php';
require_once(dirname(__FILE__) . '/../../../functions/functions.php');

$client=$_SERVER['REMOTE_ADDR'];
if ($_SERVER['HTTP_USER_AGENT']=='KVM-VDI client')
    $html5_client=0;
else
    $html5_client=1;
slash_vars();
if (!check_client_session()){
    header ("Location: $serviceurl/client_index.php?error=1");
    exit;
}
if (isset($_POST['protocol']))
    $protocol=$_POST['protocol'];
if (isset($_POST['pool']))
    $pool=$_POST['pool'];
if (isset($_POST['username']))
    $username=$_POST['username'];
if (isset($_POST['password']))
    $password=$_POST['password'];
if (isset($_POST['use_hypervisor_address']))
    $use_hypervisor_address=$_POST['use_hypervisor_address'];
$userid=$_SESSION['userid'];

if ($protocol=="SPICE"){
    $reset=0;
    //First lets check if theres already machine provided for this user and it was last acessed within 5mins (takeover from another thin client).
    $suggested_vm=getSQLArray("SELECT vms.*, poolmap_vm.poolid, poolmap_vm.vmid, hypervisors.ip FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.source_volume
    LEFT JOIN hypervisors ON vms.osHypervisorName=hypervisors.name WHERE poolmap_vm.poolid='$pool' AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE)
    AND vms.clientid = '$userid' AND vms.maintenance != 'true' AND vms.locked='false' LIMIT 1");
    if (empty($suggested_vm)){//if there's no VMs to take over, get new available VM (the one which was accesed more than '$return_to_pool_after' minutes ago).
    //we update first available machine first (to avoid race conditions)
        add_SQL_line("UPDATE vms JOIN (SELECT poolmap_vm.vmid FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.source_volume
        LEFT JOIN hypervisors ON vms.osHypervisorName = hypervisors.name WHERE poolmap_vm.poolid = '$pool' AND vms.locked='false'
        AND vms.lastused < DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE) ORDER BY RAND() LIMIT 1) tmp ON vms.source_volume = tmp.vmid SET vms.clientid='$userid',vms.lastused=NOW()");

        $suggested_vm=getSQLArray("SELECT vms.*, poolmap_vm.poolid, poolmap_vm.vmid, hypervisors.ip FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.source_volume
        LEFT JOIN hypervisors ON vms.osHypervisorName =  hypervisors.name WHERE poolmap_vm.poolid='$pool' AND vms.locked = 'false' AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE)
        AND vms.clientid = '$userid' AND vms.maintenance != 'true'");
        $reset=1;
    }
    if (empty($suggested_vm)){//if there are no available VMs in pool, return error and exit
        echo json_encode(array('status'=>"NO_FREE_VMS"));
        exit;
    }
    add_SQL_line("UPDATE vms SET clientid='$userid',lastused=NOW() WHERE id='{$suggested_vm[0]['id']}'");
    $vm_status = getVMInfo($suggested_vm[0]['osInstanceId']);
    if ($vm_status['server']['status'] == 'SHUTOFF' || $vm_status['server']['OS-EXT-STS:power_state']  != 1){
        if ($vm_status['server']['OS-EXT-STS:power_state']  == 7) // if VM is suspended, force restart
            vmPowerCycle($suggested_vm[0]['osInstanceId'], 'resume');
        else 
            vmPowerCycle($suggested_vm[0]['osInstanceId'], 'up');
        $json_reply = json_encode(array('status'=>"BOOTUP",'protocol' => $protocol, 'address' => ''));
    }
    else{
        if (!$html5_client){
            $command = array();
            $command['command'] = 'make-spice-channel';
            $command['hypervisor_ip'] = $suggested_vm[0]['ip'];
            $command['spice_password'] = $suggested_vm[0]['spice_password'];
            $command['spice_port'] = $suggested_vm[0]['osInstancePort'];
            $command['vm_id'] = $suggested_vm[0]['osInstanceId'];
            $command = json_encode($command);
            $reply = sendToBroker($command);
            if (!$reply){
                echo json_encode(array('status' => 'socket-failed'));
                exit;
                }
            else{
                $reply = json_decode($reply, TRUE);
                $reply['spice_address'] = $kvm_vdi_broker_spice_address;
                $reply['spice_password'] = $vm_array[0]['spice_password'];
            }
            $json_reply = json_encode(array('status'=>"OK",'protocol' => $protocol, 'address' => $kvm_vdi_broker_spice_address . ':' . $reply['spice_port'], 'spice_password' => $suggested_vm[0]['spice_password'], 'name' => $suggested_vm[0]['name'], 'vm_id' => $suggested_vm[0]['osInstanceId']));
        }
        else {
            if ($use_kvmvdi_html5_client){// generate json reply for KVM-VDI html5 client
                $json_reply = json_encode(array('status' => 'OK', 'address' => $websockets_address, 'port' => $websockets_port, 'spice_password' => $suggested_vm[0]['spice_password'], 'token' => $suggested_vm[0]['osInstanceName'], 'value' => $suggested_vm[0]['ip'] . ':' . $suggested_vm[0]['osInstancePort']));
                }
            else {
               $console = json_decode(listConsoles($suggested_vm[0]['osInstanceId']), TRUE);
               $json_reply = json_encode(array('status'=>"OK",'protocol' => $protocol, 'html5_url' => $console['console']['url'] . '&password=' . $suggested_vm[0]['spice_password'], 'vm_id' => $suggested_vm[0]['osInstanceId'], 'kvmvdi-html5-client' => $use_kvmvdi_html5_client));
            }
        }
    }
}
echo $json_reply;
add_sql_line("INSERT INTO log (ip,message, date) VALUES ('$client','$json_reply', NOW())");
