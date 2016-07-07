<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$side=$_GET['side'];
$poolid=$_GET['poolid'];
$client_array=get_SQL_array("SELECT clients.id, clients.username FROM `clients` LEFT JOIN poolmap ON clients.id=poolmap.clientid WHERE poolmap.poolid='$poolid' ORDER BY id");
if ($side=="from"){
    $client_array_full=get_SQL_array("SELECT clients.id,clients.username FROM `clients` ORDER BY id");
    if (!empty ($client_array)){
	$clients= array_diff ($client_array_full,$client_array);
	foreach($client_array_full as $aV){
    	    $aTmp1[] = $aV['id'];
    	    $aTmp1[] = $aV['username'];
	}
	foreach($client_array as $aV){
    	    $aTmp2[] = $aV['id'];
    	    $aTmp2[] = $aV['username'];
	}
	$tmp_array = array_diff($aTmp1,$aTmp2);
	$tmp=$mode = current($tmp_array);
	$x=0;
	$client_array=array();
	while ($tmp){
    	    $client_array[$x]['id']=$tmp;
    	    $tmp = next($tmp_array);
    	    $client_array[$x]['username']=$tmp;
    	    $tmp = next($tmp_array);
    	    ++$x;
	    }
	}
	else{
	    unset($client_array);
	    $client_array=$client_array_full;
	}
}
echo json_encode($client_array);
?>
