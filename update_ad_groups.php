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
    if (!empty($_POST['groupname']))
	$groupname=$_POST['groupname'];
    else{
	echo  'EMPTY_GROUP';
	exit;
    }
    $existing=get_SQL_line("SELECT id FROM ad_groups WHERE name = '$groupname'");
    if (!empty($existing[0])){
	echo 'EXISTS';
	exit;
    }
    add_SQL_line("INSERT INTO ad_groups (name) VALUES ('$groupname')");
    echo 'SUCCESS';
    exit;
}
if ($type=='delete'){
    $group=$_POST['group'];
    foreach ($user as $id){
    	    add_SQL_line("DELETE FROM ad_groups WHERE id='$id' LIMIT 1");
    }
}
?>
