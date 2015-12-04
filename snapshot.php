<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
$vm=addslashes($_GET['vm']);
$hypervisor=addslashes($_GET['hypervisor']);
$action=addslashes($_GET['action']);
if (empty($vm)){
    header("Location: $serviceurl/dashboard.php");
    exit;
}
if ($action=="mass_on")
    add_SQL_line("UPDATE vms SET snapshot='true' WHERE source_volume='$vm'");
if ($action=="mass_off")
    add_SQL_line("UPDATE vms SET snapshot='false' WHERE source_volume='$vm'");
if ($action=="single"){
    $snapshot=get_SQL_line("SELECT snapshot FROM vms WHERE id='$vm'");
    if ($snapshot[0]=="true")
        add_SQL_line("UPDATE vms SET snapshot='false' WHERE id='$vm'");
    else
	add_SQL_line("UPDATE vms SET snapshot='true' WHERE id='$vm'");
}
header("Location: $serviceurl/dashboard.php");
exit;
?>
