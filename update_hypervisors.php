<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$address1=$_POST['address1'];
$type=$_POST['type'];
$address2=$_POST['address2'];
$name=$_POST['name'];
$port=$_POST['port'];
if (empty($address2))
    $address2=$address1;
if (empty($port))
    $port=22;
if ($type=='new'){
    $reply=ssh_connect($address1 . ":" . $port);
    if (empty ($reply))
	$reply='SUCCESS';
    if (!isset($address1)){
	echo 'EMPTY_ADDRESS';
	exit;
    }
    $existing=get_SQL_line("SELECT id FROM hypervisors WHERE ip = '$address1'");
    if (!empty($existing[0])){
	echo 'EXISTS';
	exit;
    }
    add_SQL_line("INSERT INTO hypervisors (name,ip, port, maintenance,address2) VALUES ('$name','$address1','$port',0,'$address2')");
}
if ($name=='update-name'){//using x-editable jQuery plugin, which uses different param naming
    $pk=$_POST['pk'];
    $value=$_POST['value'];
    add_SQL_line("UPDATE hypervisors SET name='$value' WHERE id='$pk'");
    exit;
}
if ($name=='update-address'){//using x-editable jQuery plugin, which uses different param naming
    $pk=$_POST['pk'];
    $value=$_POST['value'];
    add_SQL_line("UPDATE hypervisors SET ip='$value' WHERE id='$pk'");
    exit;
}
if ($name=='update-spice'){//using x-editable jQuery plugin, which uses different param naming
    $pk=$_POST['pk'];
    $value=$_POST['value'];
    add_SQL_line("UPDATE hypervisors SET address2='$value' WHERE id='$pk'");
    exit;
}
if ($type=='delete'){
    $hypervisor=$_POST['hypervisor'];
    foreach ($hypervisor as $id){
	add_SQL_line("DELETE FROM hypervisors WHERE id='$id'");
	add_SQL_line("DELETE FROM vms WHERE hypervisor='$id'");
    }
}
echo $reply;
exit;
?>
