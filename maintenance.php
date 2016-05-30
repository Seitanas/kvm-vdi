<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$sourcevm=$_GET['source'];
$action=$_GET['action'];
if (empty($sourcevm)){
    header("Location: $serviceurl/dashboard.php");
    exit;
}
if ($action=="single"){
    $maintenance=get_SQL_line("SELECT maintenance FROM vms WHERE id='$sourcevm'");
    if ($maintenance[0]=="true")
        add_SQL_line("UPDATE vms SET maintenance='false' WHERE id='$sourcevm'");
    else
        add_SQL_line("UPDATE vms SET maintenance='true' WHERE id='$sourcevm'");
}
if ($action=="mass_on")
    add_SQL_line("UPDATE vms SET maintenance='true' WHERE source_volume='$sourcevm'");
if ($action=="mass_off")
    add_SQL_line("UPDATE vms SET maintenance='false' WHERE source_volume='$sourcevm'");
header("Location: $serviceurl/dashboard.php");
exit;
?>