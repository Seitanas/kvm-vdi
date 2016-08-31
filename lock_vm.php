<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
slash_vars();
$vm=$_POST['vm'];
$lock=$_POST['lock'];
add_SQL_line("UPDATE vms SET locked='$lock' WHERE id='$vm' LIMIT 1");
?>