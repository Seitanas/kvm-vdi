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
$userid=$_SESSION['userid'];
if ($_SESSION['ad_user']=='yes')
    $username=$username."@".$ad_name;

if ($protocol=="RDP"){
    $json_reply = json_encode(array('status'=>"OK",'protocol' => $protocol, 'address' => $machine_rdp_address));

}

if ($protocol=="vmView"){
    $json_reply = json_encode(array('status'=>"OK",'protocol' => $protocol, 'address' => $vmView_server, 'pool' => $pool));
}

if ($protocol=="SPICE"){
    $reset=0;

    //First lets check if theres already machine provided for this user and it was last acessed within 5mins (takeover from another thin client).
    $suggested_vm=get_SQL_array("SELECT * FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id WHERE poolmap_vm.poolid='$pool' AND vms.lastused > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND vms.clientid='$userid' LIMIT 1");
    if (empty($suggested_vm)){//if there's no VMs to take over, get new available VM (the one which was accesed more than 5 minutes ago).
	//we update first available machine first (to avoid race conditions)
	add_SQL_line("UPDATE vms JOIN (SELECT poolmap_vm.vmid FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id WHERE poolmap_vm.poolid='$pool' AND vms.lastused < DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY RAND() LIMIT 1) tmp ON vms.id=tmp.vmid SET vms.clientid='$userid',vms.lastused=NOW()");
	$suggested_vm=get_SQL_array("SELECT * FROM poolmap_vm LEFT JOIN vms ON poolmap_vm.vmid=vms.id WHERE poolmap_vm.poolid='$pool' AND vms.lastused > DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND vms.clientid='$userid'");
	$reset=1;
    }
    if (empty($suggested_vm)){//if there are no available VMs in pool, return error and exit
        echo json_encode(array('status'=>"NO_FREE_VMS"));
	exit;
    }
    add_SQL_line("UPDATE vms SET clientid='$userid',lastused=NOW() WHERE id='{$suggested_vm[0]['id']}'");
    $machine_name=$suggested_vm[0]['name'];
    $vm=get_SQL_array("SELECT hypervisor,maintenance,spice_password,name FROM vms WHERE name='$machine_name'");
    $h_reply=get_SQL_array("SELECT * FROM hypervisors WHERE id='{$vm[0]['hypervisor']}'");
    if ($vm[0]['maintenance']=="true"||$h_reply[0]['maintenance']==1){
        echo json_encode(array('status'=>"MAINTENANCE"));
	exit;
    }
    ssh_connect($h_reply[0]['ip'].":".$h_reply[0]['port']);
    $status=ssh_command("sudo virsh domdisplay ".$machine_name,true);
    $status=str_replace("spice://","",$status);
    $status=str_replace("\n","",$status);
    $status=str_replace("localhost",$h_reply[0]['address2'],$status);
    if ($reset_vm&&$reset)
	ssh_command("sudo virsh reset ".$machine_name,true);
    if (empty($status)){
	$agent_command=json_encode(array('vmname' => $machine_name, 'username' => $username, 'password' => $password));
	$status='BOOTUP';
        ssh_command('echo "' . addslashes($agent_command) . '"| socat /usr/local/VDI/kvm-vdi.sock - ',true);
	reload_vm_info();	
    }
    if ($status=="BOOTUP")
	$json_reply = json_encode(array('status'=>"BOOTUP",'protocol' => $protocol, 'address' => ''));
    else if ($status)
        $json_reply = json_encode(array('status'=>"OK",'protocol' => $protocol, 'address' => $status, 'spice_password' => $vm[0]['spice_password'], 'name' => $vm[0]['name']));
    else
	$json_reply = json_encode(array('status'=>"FAIL",'protocol' => $protocol, 'address' => ''));
}
echo $json_reply;
add_sql_line("INSERT INTO log (ip,message, date) VALUES ('$client','$json_reply', NOW())");
?>