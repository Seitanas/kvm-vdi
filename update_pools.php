<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$type='';
if (isset($_POST['type']))
    $type=$_POST['type'];
if ($type=='new'){
    if (!empty($_POST['poolname']))
	$poolname=$_POST['poolname'];
    else{
	echo  'EMPTY_POOL';
	exit;
    }
    $existing=get_SQL_line("SELECT id FROM pool WHERE name = '$poolname'");
    if (!empty($existing[0])){
	echo 'EXISTS';
	exit;
    }
    add_SQL_line("INSERT INTO pool (name) VALUES ('$poolname')");
    echo 'SUCCESS';
    exit;
}
if ($type=='delete'){
    $user=$_POST['user'];
    foreach ($user as $id){
    	    add_SQL_line("DELETE FROM pool WHERE id='$id' LIMIT 1");
    }
}
?>
