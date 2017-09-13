<?php
include("functions/config.php");
require_once('functions/functions.php');
$client=$_SERVER['REMOTE_ADDR'];
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
    $suggested_vm=get_SQL_array("SELECT vms.*, poolmap_vm.poolid, poolmap_vm.vmid FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id WHERE hypervisors.maintenance='0' AND poolmap_vm.poolid='$pool' AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE) AND vms.clientid='$userid' AND vms.maintenance='false' AND vms.locked='false' LIMIT 1");
    if (empty($suggested_vm)){//if there's no VMs to take over, get new available VM (the one which was accesed more than '$return_to_pool_after' minutes ago).
    //we update first available machine first (to avoid race conditions)
        add_SQL_line("UPDATE vms JOIN (SELECT poolmap_vm.vmid FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id WHERE hypervisors.maintenance='0' AND poolmap_vm.poolid='$pool' AND vms.locked='false' AND vms.lastused < DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE) ORDER BY RAND() LIMIT 1) tmp ON vms.id=tmp.vmid SET vms.clientid='$userid',vms.lastused=NOW()");
        $suggested_vm=get_SQL_array("SELECT vms.*, poolmap_vm.poolid, poolmap_vm.vmid FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id LEFT JOIN hypervisors ON vms.hypervisor=hypervisors.id WHERE hypervisors.maintenance='0' AND poolmap_vm.poolid='$pool' AND vms.locked='false' AND vms.lastused > DATE_SUB(NOW(), INTERVAL '$return_to_pool_after' MINUTE) AND vms.clientid='$userid' AND vms.maintenance='false'");
        $reset=1;
    }
    if (empty($suggested_vm)){//if there are no available VMs in pool, return error and exit
        echo json_encode(array('status'=>"NO_FREE_VMS"));
    exit;
    }
    add_SQL_line("UPDATE vms SET clientid='$userid',lastused=NOW() WHERE id='{$suggested_vm[0]['id']}'");
    $machine_name=$suggested_vm[0]['name'];
    $vm=get_SQL_array("SELECT hypervisor,maintenance,spice_password,name,os_type FROM vms WHERE name='$machine_name'");
    $h_reply=get_SQL_array("SELECT * FROM hypervisors WHERE id='{$vm[0]['hypervisor']}'");
    if ($vm[0]['maintenance']=="true"||$h_reply[0]['maintenance']==1){
        echo json_encode(array('status'=>"MAINTENANCE"));
	exit;
    }
    ssh_connect($h_reply[0]['ip'].":".$h_reply[0]['port']);
    $status=ssh_command("sudo virsh domdisplay ".$machine_name, true, true);
    $status=str_replace("spice://","",$status);
    $status=str_replace("\n","",$status);
    if ($use_hypervisor_address==1)
        $status=str_replace("localhost",$h_reply[0]['ip'],$status);
    else
        $status=str_replace("localhost",$h_reply[0]['address2'],$status);
    if ($_SESSION['ad_user']=='yes'&&$vm[0]['os_type']=='windows')//we only need to pass username@domainname to windows login.
        $username=$username."@".$domain_name;
    $agent_command=json_encode(array('command' => 'STARTVM', 'vmname' => $machine_name, 'username' => $username, 'password' => $password, 'os_type' => $vm[0]['os_type']));
    if (empty($status)||$status=='error: Domain is not running'){
        add_SQL_line("UPDATE vms SET spice_password='' WHERE id='{$suggested_vm[0]['id']}'"); // reset SPICE password for shut off VMs
        $status='BOOTUP';
        ssh_command('echo "' . addslashes($agent_command) . '"| socat /usr/local/VDI/kvm-vdi.sock - ',true);
        reload_vm_info();
    }
    else if ($reset_vm&&$reset){
        ssh_command("sudo virsh destroy ".$machine_name,true);
        add_SQL_line("UPDATE vms SET spice_password='' WHERE id='{$suggested_vm[0]['id']}'");// reset SPICE password for shut off VMs
        $status='BOOTUP';
        ssh_command('echo "' . addslashes($agent_command) . '"| socat /usr/local/VDI/kvm-vdi.sock - ',true);
        reload_vm_info();
    }
    if ($vm[0]['spice_password'] == '') // if this is a newly bootet machine, wait for new password to be populated
        $status = 'BOOTUP';
    if ($status=='BOOTUP')
        $json_reply = json_encode(array('status'=>"BOOTUP",'protocol' => $protocol, 'address' => ''));
    else if ($status){
        $json_reply = json_encode(array('status'=>"OK",'protocol' => $protocol, 'address' => $status, 'spice_password' => $vm[0]['spice_password'], 'name' => $vm[0]['name']));
    }
    else
	$json_reply = json_encode(array('status'=>"FAIL",'protocol' => $protocol, 'address' => ''));
}
echo $json_reply;
add_sql_line("INSERT INTO log (ip,message, date) VALUES ('$client','$json_reply', NOW())");
