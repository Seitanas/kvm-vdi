<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
/*
$x=0;
while ($hypervizors[$x]){
    $tmp = explode(":", $hypervizors[$x]);
    $ip=$tmp[0];
    $port=$tmp[1];
    $sql_reply=get_SQL_line("SELECT id FROM hypervisors WHERE ip='$ip'");
    if (empty($sql_reply[0]))
	add_SQL_line("INSERT INTO  hypervisors (ip,port) VALUES ('$ip','$port')");
    else
	add_SQL_line("UPDATE hypervisors SET ip='$ip', port='$port' WHERE id='$sql_reply[0]'");
    $sql_reply=get_SQL_line("SELECT id FROM hypervisors WHERE ip='$ip'");
    $hyper_id=$sql_reply[0];
    ssh_connect($ip . ":" . $port);
    $output = ssh_command("sudo virsh list --all |tail -n +3|head -n -1|awk '{print $2" . '" "' . "$3}'",true);
    $vms=array();
    $output=str_replace("\n"," ",$output);
    $vms=explode(" ",$output);
//  print_r($vms);
    $y=0;
    while ($vms[$y]){
	$sql_reply=get_SQL_line("SELECT id FROM vms WHERE name='$vms[$y]' AND hypervisor='$hyper_id'");
	$state=$vms[$y+1];
        if (empty($sql_reply[0]))
		add_SQL_line("INSERT INTO  vms (name,hypervisor,state) VALUES ('$vms[$y]','$hyper_id','$state')");
        else
		add_SQL_line("UPDATE vms SET name='$vms[$y]', hypervisor='$hyper_id', state='$state' WHERE id='$sql_reply[0]'");
	$y=$y+2;
    }
            //echo $output . " " . $error;
    ++$x;
}
//    echo $output . " " . $error;

*/
reload_vm_info();
header("Location: $serviceurl/dashboard.php");
exit;
?>
