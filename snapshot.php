<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vm=$_GET['vm'];
$hypervisor=$_GET['hypervisor'];
$action=$_GET['action'];
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
