<?php
include ('functions/config.php');
require_once('functions/functions.php');
if (!check_session()){
    header ("Location: $serviceurl/?error=1");
    exit;
}
$maintenance=addslashes($_GET['maintenance']);
$id=addslashes($_GET['id']);


if (empty($id)){
    header("Location: $serviceurl/dashboard.php");
    exit;
}
add_SQL_line("UPDATE hypervisors SET maintenance='$maintenance' WHERE id='$id'");

header("Location: $serviceurl/dashboard.php");
exit;
?>