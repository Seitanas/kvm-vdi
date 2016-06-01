<?php

include("functions/config.php");
require_once('functions/functions.php');

$client=$_SERVER['REMOTE_ADDR'];
$xml=simplexml_load_file("functions/clients.xml") or die("Error: Cannot create object");

$x=0;
while ($xml->client[$x]['ip']){
    if ($xml->client[$x]['ip']==$client){
	$protocol=$xml->client[$x]->{'protocol'};
	$protocol=str_replace("\n","",$protocol);
	$machine_name=$xml->client[$x]->{'machine-name'};
	$machine_rdp_address=$xml->client[$x]->{'machine-rdp-address'};
	$machine_rdp_address=str_replace("\n","",$machine_rdp_address);
	$pool=$xml->client[$x]->{'pool'};
	$pool=str_replace("\n","",$pool);
    }
    ++$x;
}
if ($protocol=="RDP"){
    $json_reply = json_encode(array('status'=>"OK",'protocol' => $protocol, 'address' => $machine_rdp_address));

}

if ($protocol=="vmView"){
    $json_reply = json_encode(array('status'=>"OK",'protocol' => $protocol, 'address' => $vmView_server, 'pool' => $pool));
}

if ($protocol=="SPICE"){
    $vm=get_SQL_line("SELECT hypervisor,maintenance,spice_password FROM vms WHERE name='$machine_name'");
    $h_reply=get_SQL_line("SELECT * FROM hypervisors WHERE id='$vm[0]'");
    if ($vm[1]=="true"||$h_reply[4]==1){
        echo json_encode(array('status'=>"MAINTENANCE"));
	exit;
    }
    ssh_connect($h_reply[2].":".$h_reply[3]);
    $status=ssh_command("sudo virsh domdisplay ".$machine_name,true);
    $status=str_replace("spice://","",$status);
    $status=str_replace("\n","",$status);
    $status=str_replace("localhost",$h_reply[2],$status);
    if (empty($status)){
	$status='BOOTUP';
        ssh_command("sudo virsh start ".$machine_name,true);
	reload_vm_info();	
    }
    if ($status=="BOOTUP")
	$json_reply = json_encode(array('status'=>"BOOTUP",'protocol' => $protocol, 'address' => ''));
    else if ($status)
        $json_reply = json_encode(array('status'=>"OK",'protocol' => $protocol, 'address' => $status, 'spice_password' => $vm[2]));
    else
	$json_reply = json_encode(array('status'=>"FAIL",'protocol' => $protocol, 'address' => ''));
}
echo $json_reply;
add_sql_line("INSERT INTO log (ip,message) VALUES ('$client','$json_reply')");
?>